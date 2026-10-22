# test_webcalendar_login.py
import pytest

from selenium import webdriver
from selenium.webdriver.common.desired_capabilities import DesiredCapabilities
from selenium.webdriver.chrome.options import Options



@pytest.fixture(scope="module")
def browser():
    chrome_options = Options()
    capabilities = DesiredCapabilities.CHROME
    capabilities['javascriptEnabled'] = True
    driver = webdriver.Remote(
        command_executor='http://localhost:4444/wd/hub',
        options=chrome_options
    )
    yield driver
    driver.quit()


def test_login_page(browser):
    browser.get("http://localhost:8080/login.php")

    # Check if the page title is "WebCalendar"
    assert browser.title == "WebCalendar"

    # Check if the login form is present
    login_form = browser.find_element(By.ID, "login-form")
    assert login_form is not None

    # Check if the username and password input fields are present
    username_input = browser.find_element(By.ID, "user")
    password_input = browser.find_element(By.ID, "password")

    assert username_input is not None
    assert password_input is not None

