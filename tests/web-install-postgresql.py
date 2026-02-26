import pytest
import os
import time
import psycopg2
import re
import subprocess
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import StaleElementReferenceException, TimeoutException

BASE_URL = os.environ.get('BASE_URL', 'http://web')
SELENIUM_URL = os.environ.get('SELENIUM_URL', 'http://chrome:4444/wd/hub')
DB_HOST = os.environ.get('WEBCALENDAR_DB_HOST', 'db')
DB_USER = os.environ.get('WEBCALENDAR_DB_LOGIN', 'webcalendar')
DB_PASS = os.environ.get('WEBCALENDAR_DB_PASSWORD', 'password')
DB_NAME = os.environ.get('WEBCALENDAR_DB_DATABASE', 'webcalendar_test')

SCREENSHOT_DIR = os.environ.get('SCREENSHOT_DIR', '/work/tests/screenshots')

@pytest.fixture
def driver(request):
    chrome_options = Options()
    driver = webdriver.Remote(command_executor=SELENIUM_URL, options=chrome_options)
    driver.set_window_size(1920, 1080)
    driver.implicitly_wait(10)
    yield driver
    if hasattr(request.node, 'rep_call') and request.node.rep_call.failed:
        os.makedirs(SCREENSHOT_DIR, exist_ok=True)
        path = f"{SCREENSHOT_DIR}/postgresql-{request.node.name}.png"
        driver.save_screenshot(path)
        print(f"Screenshot saved: {path}")
    driver.quit()

def wait_for_text(driver, selector, text, timeout=15):
    end_time = time.time() + timeout
    while time.time() < end_time:
        try:
            element = driver.find_element(By.ID, selector)
            if text in element.text:
                return True
        except Exception:
            pass
        time.sleep(0.5)
    raise TimeoutException(f"Timed out waiting for text '{text}' in element '{selector}' (current text: '{driver.find_element(By.ID, selector).text if driver.find_elements(By.ID, selector) else 'N/A'}')")

def click_button(driver, selector, by=By.CSS_SELECTOR):
    try:
        element = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((by, selector)))
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", element)
        time.sleep(0.5)
        element.click()
    except Exception as e:
        print(f"Standard click failed for {selector}, trying JS click. Error: {e}")
        try:
            element = driver.find_element(by, selector)
            driver.execute_script("arguments[0].click();", element)
        except Exception:
            pass

def reset_db():
    # Remove stale settings.php if it exists
    settings_path = "/work/includes/settings.php"
    if os.path.exists(settings_path):
        try:
            os.remove(settings_path)
            print(f"Removed stale settings file: {settings_path}")
        except Exception as e:
            print(f"Warning: Could not remove {settings_path}: {e}")

    conn = psycopg2.connect(host=DB_HOST, user=DB_USER, password=DB_PASS, database='postgres')
    conn.autocommit = True
    with conn.cursor() as cursor:
        cursor.execute(f"DROP DATABASE IF EXISTS {DB_NAME}")
        cursor.execute(f"CREATE DATABASE {DB_NAME}")
    conn.close()

def load_fixture(fixture_path):
    conn = psycopg2.connect(host=DB_HOST, user=DB_USER, password=DB_PASS, database=DB_NAME)
    conn.autocommit = True
    with conn.cursor() as cursor:
        with open(fixture_path, 'r') as f:
            sql = f.read()
            # Remove comments
            sql = re.sub(r'--.*', '', sql)
            # Split by semicolon and execute
            for statement in sql.split(';'):
                statement = statement.strip()
                if statement:
                    cursor.execute(statement)
    conn.close()

def _try_login(driver, password):
    """Attempt to login as admin with the given password. Returns True if successful."""
    driver.delete_all_cookies()
    driver.get(f"{BASE_URL}/login.php")
    time.sleep(2)

    if "Fatal" in driver.title or "Error" in driver.title:
        body = driver.find_element(By.TAG_NAME, "body").text[:500]
        print(f"Smoke test: error page title='{driver.title}', body={body}")
        return False
    if "wizard" in driver.current_url:
        print(f"Smoke test: redirected to wizard ({driver.current_url})")
        return False

    try:
        WebDriverWait(driver, 10).until(EC.presence_of_element_located((By.ID, "user")))
    except TimeoutException:
        print(f"Smoke test: login form not found (title={driver.title})")
        return False

    driver.find_element(By.ID, "user").send_keys("admin")
    driver.find_element(By.ID, "password").send_keys(password)
    driver.find_element(By.CSS_SELECTOR, "#login-form button[type='submit']").click()
    time.sleep(3)

    if "login" in driver.current_url:
        body = driver.find_element(By.TAG_NAME, "body").text[:500]
        print(f"Smoke test: login with '{password}' failed. body={body}")
        return False

    print(f"Smoke test: login with '{password}' succeeded. URL={driver.current_url}")
    return True


def _post_install_smoke_test(driver):
    """After wizard completes, verify the app works: login, view calendar, create event."""
    logged_in = _try_login(driver, "admin")
    if not logged_in:
        logged_in = _try_login(driver, "admin123")

    assert logged_in, f"Smoke test: login failed with both passwords. URL={driver.current_url}"
    print(f"SUCCESS: Logged in as admin, landed on {driver.current_url}")

    # Create an event
    driver.get(f"{BASE_URL}/edit_entry.php")
    WebDriverWait(driver, 10).until(EC.presence_of_element_located((By.ID, "entry_brief")))
    driver.find_element(By.ID, "entry_brief").send_keys("Smoke Test Event")
    driver.find_element(By.CSS_SELECTOR, "button[onclick*='validate_and_submit']").click()
    time.sleep(3)

    WebDriverWait(driver, 10).until(
        lambda d: "edit_entry" not in d.current_url
    )
    print(f"SUCCESS: Event created, redirected to {driver.current_url}")


def test_new_installation(driver):
    try:
        reset_db()
        driver.get(f"{BASE_URL}/wizard/index.php")
        # Click the Start Over button to properly reset the wizard
        try:
            start_over_btn = driver.find_element(By.ID, "logoutBtn")
            driver.execute_script("arguments[0].click();", start_over_btn)
            time.sleep(1)
        except Exception:
            pass  # Already on welcome page or button not found
        wait_for_text(driver, "stepTitle", "Welcome")
        click_button(driver, "button[data-action='welcome-continue']")
        wait_for_text(driver, "stepTitle", "Authentication")
        driver.find_element(By.ID, "password").send_keys("Test123!")
        click_button(driver, "form[data-action='login'] button[type='submit']")

        last_title = "Authentication"
        for i in range(10):
            try:
                WebDriverWait(driver, 10).until(lambda d: d.find_element(By.ID, "stepTitle").text != last_title)
                title = driver.find_element(By.ID, "stepTitle").text
                last_title = title
                if "Tables" in title: break
                btns = driver.find_elements(By.CSS_SELECTOR, "button.btn-primary, button.btn-success")
                clicked = False
                for btn in btns:
                    if btn.is_displayed() and ("Continue" in btn.text or "Acknowledge" in btn.text):
                        driver.execute_script("arguments[0].click();", btn)
                        clicked = True
                        break
                if not clicked: break
            except Exception: break

        # Wait for either Tables (upgrade path) or Database (new install path)
        try:
            wait_for_text(driver, "stepTitle", "Tables")
            click_button(driver, "button[data-action='execute-upgrade']")
        except TimeoutException:
            wait_for_text(driver, "stepTitle", "Database")
            click_button(driver, "button[data-action='continue-db-readonly']")
            wait_for_text(driver, "stepTitle", "Tables")
            click_button(driver, "button[data-action='execute-upgrade']")

        # Click through Admin User / Summary / Finish
        for i in range(10):
            try:
                title = driver.find_element(By.ID, "stepTitle").text
                if "Admin User" in title:
                    driver.find_element(By.ID, "admin_password").send_keys("admin123")
                    driver.find_element(By.ID, "admin_password2").send_keys("admin123")
                    driver.find_element(By.ID, "admin_email").send_keys("admin@example.com")
                    click_button(driver, "button[data-action='create-admin-user']")
                elif "Summary" in title:
                    save_btns = driver.find_elements(By.CSS_SELECTOR, "button[data-action='save-settings-file']")
                    if save_btns:
                        click_button(driver, "button[data-action='save-settings-file']")
                    else:
                        click_button(driver, "continueToFinishBtn", by=By.ID)
                elif "Finish" in title:
                    break
            except Exception: pass
            time.sleep(1)

        wait_for_text(driver, "stepTitle", "Finish")
        assert "Complete" in driver.page_source

        # Post-install smoke test: login, view calendar, create event
        _post_install_smoke_test(driver)
    except Exception:
        print(f"FAILED on page: {driver.current_url}")
        raise

def _run_upgrade_test(driver, fixture_path):
    """Shared upgrade test logic: load fixture, run wizard, verify finish."""
    try:
        reset_db()
        load_fixture(fixture_path)
        time.sleep(2)

        driver.delete_all_cookies()
        driver.get(f"{BASE_URL}/wizard/index.php")

        try:
            start_over_btn = driver.find_element(By.ID, "logoutBtn")
            driver.execute_script("arguments[0].click();", start_over_btn)
            time.sleep(1)
        except Exception:
            pass

        wait_for_text(driver, "stepTitle", "Welcome")
        click_button(driver, "button[data-action='welcome-continue']")
        wait_for_text(driver, "stepTitle", "Authentication")
        driver.find_element(By.ID, "password").send_keys("Test123!")
        click_button(driver, "form[data-action='login'] button[type='submit']")

        try:
            wait_for_text(driver, "stepTitle", "Tables")
        except TimeoutException:
            wait_for_text(driver, "stepTitle", "Database")
            click_button(driver, "button[data-action='continue-db-readonly']")
            wait_for_text(driver, "stepTitle", "Tables")

        print(f"SUCCESS: Login successful - reached Tables step (fixture: {fixture_path})")
        click_button(driver, "button[data-action='execute-upgrade']")

        for i in range(20):
            try:
                title = driver.find_element(By.ID, "stepTitle").text
                if "Summary" in title:
                    save_btns = driver.find_elements(By.CSS_SELECTOR, "button[data-action='save-settings-file']")
                    if save_btns:
                        click_button(driver, "button[data-action='save-settings-file']")
                    else:
                        click_button(driver, "continueToFinishBtn", by=By.ID)
                    time.sleep(1)
                if "Finish" in title: break
            except Exception:
                pass
            time.sleep(1)

        wait_for_text(driver, "stepTitle", "Finish")
        assert "Complete" in driver.page_source
    except Exception:
        print(f"FAILED on page: {driver.current_url}")
        try:
            print(f"Page Title: {driver.title}")
            print(f"Step Title: {driver.find_element(By.ID, 'stepTitle').text if driver.find_elements(By.ID, 'stepTitle') else 'N/A'}")
        except Exception:
            pass
        raise

def test_upgrade_installation(driver):
    _run_upgrade_test(driver, "tests/fixtures/v1.3.0-schema-postgresql.sql")

def test_upgrade_from_v1_9_10(driver):
    _run_upgrade_test(driver, "tests/fixtures/v1.9.10-schema-postgresql.sql")

def test_upgrade_from_v1_9_12(driver):
    _run_upgrade_test(driver, "tests/fixtures/v1.9.12-schema-postgresql.sql")

def get_expected_version():
    """Get expected version from bump_version.sh or fallback to parsing config file"""
    try:
        # Try to run bump_version.sh (pytest container uses /work)
        result = subprocess.run(
            ["bash", "./bump_version.sh", "-p"],
            capture_output=True,
            text=True,
            cwd="/work"
        )
        version = result.stdout.strip()
        if version:
            return version
    except Exception:
        pass
    
    # Fallback: parse directly from default_config.php
    try:
        with open("/work/wizard/shared/default_config.php", "r") as f:
            content = f.read()
            match = re.search(r"'WEBCAL_PROGRAM_VERSION' => '([^']+)'", content)
            if match:
                return match.group(1).lstrip('v')
    except Exception:
        pass
    
    return None

def test_version_check(driver):
    """Check that the database and wizard both report the correct program version."""
    try:
        expected_version = get_expected_version()
        print(f"Expected version: {expected_version}")
        assert expected_version, "Could not determine expected version"

        # 1. Verify version in database directly
        conn = psycopg2.connect(host=DB_HOST, user=DB_USER, password=DB_PASS, database=DB_NAME)
        with conn.cursor() as cursor:
            cursor.execute("SELECT cal_value FROM webcal_config WHERE cal_setting = 'WEBCAL_PROGRAM_VERSION'")
            result = cursor.fetchone()
        conn.close()

        assert result, "WEBCAL_PROGRAM_VERSION not found in webcal_config"
        db_version = result[0].lstrip('v')
        print(f"Version in DB: {db_version}")
        assert db_version == expected_version, f"DB version mismatch: {db_version} != {expected_version}"

        # 2. Verify version shown by wizard status page
        driver.get(f"{BASE_URL}/wizard/index.php")
        time.sleep(1)
        page_source = driver.page_source
        match = re.search(r'v([0-9]+\.[0-9]+\.[0-9]+)', page_source)
        assert match, "Could not find version in wizard page"
        wizard_version = match.group(1)
        print(f"Version in wizard: {wizard_version}")
        assert wizard_version == expected_version, f"Wizard version mismatch: {wizard_version} != {expected_version}"

        print(f"SUCCESS: Version check passed - DB={db_version}, wizard={wizard_version}")

    except Exception:
        print(f"FAILED on page: {driver.current_url}")
        raise
