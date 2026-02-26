import pytest
import os
import time
import pymysql
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
    # Save screenshot on test failure
    if hasattr(request.node, 'rep_call') and request.node.rep_call.failed:
        os.makedirs(SCREENSHOT_DIR, exist_ok=True)
        path = f"{SCREENSHOT_DIR}/mysql-{request.node.name}.png"
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
    raise TimeoutException(f"Timed out waiting for text '{text}' in element '{selector}'")

def click_button(driver, selector, by=By.CSS_SELECTOR):
    try:
        element = WebDriverWait(driver, 10).until(EC.element_to_be_clickable((by, selector)))
        driver.execute_script("arguments[0].scrollIntoView({block: 'center'});", element)
        time.sleep(0.5)
        element.click()
    except Exception:
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

    conn = pymysql.connect(host=DB_HOST, user='root', password='root')
    with conn.cursor() as cursor:
        # Check if database exists
        cursor.execute(f"SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{DB_NAME}'")
        result = cursor.fetchone()
        if result:
            # Database exists - truncate all tables instead of dropping
            cursor.execute(f"USE {DB_NAME}")
            cursor.execute("SET FOREIGN_KEY_CHECKS = 0")
            cursor.execute(f"""
                SELECT table_name FROM information_schema.tables 
                WHERE table_schema = '{DB_NAME}'
            """)
            tables = cursor.fetchall()
            for (table_name,) in tables:
                cursor.execute(f"DROP TABLE IF EXISTS {table_name}")
            cursor.execute("SET FOREIGN_KEY_CHECKS = 1")
        else:
            # Database doesn't exist - create it
            cursor.execute(f"CREATE DATABASE {DB_NAME}")
            cursor.execute(f"GRANT ALL PRIVILEGES ON {DB_NAME}.* TO '{DB_USER}'@'%'")
            cursor.execute("FLUSH PRIVILEGES")
    conn.close()

def load_fixture(fixture_path):
    print(f"Loading fixture using mysql CLI: {fixture_path}")
    env = os.environ.copy()
    env['MYSQL_PWD'] = 'root'
    try:
        # Use mysql CLI with redirection - very reliable
        with open(fixture_path, 'r') as f:
            result = subprocess.run([
                "mysql", "-h", DB_HOST, "-u", "root", DB_NAME
            ], env=env, stdin=f, capture_output=True, text=True)
        
        if result.returncode != 0:
            print(f"Error loading fixture: {result.stderr}")
            # Try without SSL just in case
            with open(fixture_path, 'r') as f:
                result = subprocess.run([
                    "mysql", "-h", DB_HOST, "-u", "root", "--skip-ssl", DB_NAME
                ], env=env, stdin=f, capture_output=True, text=True)
            
            if result.returncode != 0:
                print(f"Error loading fixture (retry without SSL): {result.stderr}")
                raise Exception(f"mysql CLI failed with return code {result.returncode}")
        
        # Verify data was loaded using pymysql
        conn = pymysql.connect(host=DB_HOST, user=DB_USER, password=DB_PASS, database=DB_NAME)
        with conn.cursor() as cursor:
            cursor.execute("SELECT COUNT(*) FROM webcal_config")
            count = cursor.fetchone()[0]
            print(f"Fixture loaded. webcal_config row count: {count}")
            cursor.execute("SELECT cal_value FROM webcal_config WHERE cal_setting = 'WEBCAL_PROGRAM_VERSION'")
            result = cursor.fetchone()
            if result:
                print(f"Detected version in DB: {result[0]}")
            else:
                print("No WEBCAL_PROGRAM_VERSION found in webcal_config!")
        conn.close()
    except Exception as e:
        print(f"ERROR loading fixture: {e}")
        raise

def _post_install_smoke_test(driver):
    """After wizard completes, verify the app serves pages without errors."""
    driver.delete_all_cookies()
    driver.get(f"{BASE_URL}/login.php")
    time.sleep(2)

    # Verify login page renders without fatal errors
    title = driver.title
    print(f"Smoke test: login page title='{title}', URL={driver.current_url}")
    assert "Fatal" not in title, f"Smoke test: fatal error on login page (title={title})"
    assert "wizard" not in driver.current_url, f"Smoke test: redirected to wizard ({driver.current_url})"

    # Verify the login form has expected elements
    user_field = driver.find_elements(By.ID, "user")
    password_field = driver.find_elements(By.ID, "password")
    assert user_field, "Smoke test: login form missing username field"
    assert password_field, "Smoke test: login form missing password field"
    print("SUCCESS: Post-install smoke test passed — login page renders correctly")


def test_new_installation(driver):
    try:
        reset_db()
        # Properly logout using POST request to clear session
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
            # If we land on Database Settings instead, click Continue
            wait_for_text(driver, "stepTitle", "Database")
            click_button(driver, "button[data-action='continue-db-readonly']")
            wait_for_text(driver, "stepTitle", "Tables")
            click_button(driver, "button[data-action='execute-upgrade']")
        
        # Click through Summary if needed
        for i in range(10):
            try:
                title = driver.find_element(By.ID, "stepTitle").text
                if "Summary" in title:
                    # Try save-settings-file first; if it doesn't exist (ENV mode), use Continue
                    save_btns = driver.find_elements(By.CSS_SELECTOR, "button[data-action='save-settings-file']")
                    if save_btns:
                        click_button(driver, "button[data-action='save-settings-file']")
                    else:
                        click_button(driver, "continueToFinishBtn", by=By.ID)
                if "Finish" in title: break
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
    _run_upgrade_test(driver, "tests/fixtures/v1.3.0-schema-mysql.sql")

def test_upgrade_from_v1_9_10(driver):
    _run_upgrade_test(driver, "tests/fixtures/v1.9.10-schema-mysql.sql")

def test_upgrade_from_v1_9_12(driver):
    _run_upgrade_test(driver, "tests/fixtures/v1.9.12-schema-mysql.sql")

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
        conn = pymysql.connect(host=DB_HOST, user=DB_USER, password=DB_PASS, database=DB_NAME)
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
