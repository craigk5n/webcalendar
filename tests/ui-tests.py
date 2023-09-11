import httpx
from lxml import html
import pytest
from datetime import datetime
import uuid

BASE_URL = "http://localhost:8080"
ADMIN_USERNAME = "admin"
ADMIN_PASSWORD = "admin"

@pytest.fixture(scope="module")
def http_client():
    with httpx.Client() as client:
        # Load the login page
        response = client.get(f"{BASE_URL}/login.php")
        assert response.status_code == 200

        # Parse the HTML content
        tree = html.fromstring(response.text)

        # Check if the form elements exist
        assert tree.xpath('//input[@id="user"]')
        assert tree.xpath('//input[@id="password"]')
    
        # Login with the given credentials
        login_data = {
            "login": ADMIN_USERNAME,
            "password": ADMIN_PASSWORD
        }
        login_response = client.post(f"{BASE_URL}/login.php", data=login_data)

        # Ensure the login was successful
        assert login_response.status_code == 302
        yield client  # This client will be used in the tests and it is already authenticated


def test_add_event_missing_csrf(http_client):
    # Load the edit_entry page
    response = http_client.get(f"{BASE_URL}/edit_entry.php")
    assert response.status_code == 200

    # Parse the HTML content
    tree = html.fromstring(response.text)

    # Check if the form elements exist
    assert tree.xpath('//input[@id="entry_brief"]')
    assert tree.xpath('//input[@id="_YMD"]')
    
    # Set the description and date (20091025)
    current_date = datetime.now()
    formatted_date = current_date.strftime('%Y%m%d')
    login_data = {
        "_YMD": formatted_date,
        "name": "Sample Event #1"
    }
    add_response = http_client.post(f"{BASE_URL}/edit_entry_handler.php", data=login_data)

    # Successful save will give 302 redirect to month.php
    assert add_response.status_code == 200 # which means that save failed
    assert "Invalid form request" in add_response.text
    print(add_response.text)
    # TODO: Add additional cases that should fail: Missing brief descriptions, invalid dates, permission denied,
    # invalid or missing timetype, etc.


def test_add_event(http_client):
    # Load the edit_entry page
    response = http_client.get(f"{BASE_URL}/edit_entry.php")
    assert response.status_code == 200

    # Parse the HTML content
    tree = html.fromstring(response.text)

    # Check if the form elements exist
    assert tree.xpath('//input[@id="entry_brief"]')
    assert tree.xpath('//input[@id="_YMD"]')
    csrf_token = tree.xpath('//input[@name="csrf_form_key"]/@value')[0]
    assert csrf_token

    # Set the description and date (20091025)
    current_date = datetime.now()
    formatted_date = current_date.strftime('%Y%m%d')
    # Generate a random UUID
    event_uuid = str(uuid.uuid4())
    event_name = "Event " + event_uuid
    login_data = {
        "csrf_form_key": csrf_token,
        "_YMD": formatted_date,
        "name": event_name,
        "timetype": "U"
    }
    add_response = http_client.post(f"{BASE_URL}/edit_entry_handler.php", data=login_data)

    # Successful save will give 302 redirect to month.php
    assert add_response.status_code == 302
    print(add_response.text)

def test_get_event(http_client):
    # Now use activity_log.php to see if the event shows up
    response = httpx.get(f"{BASE_URL}/activity_log.php")
    content = response.text
    print(response.text)
    # Parse the HTML
    tree = html.fromstring(content)
    import httpx
from lxml import html
import pytest
from datetime import datetime
import uuid

BASE_URL = "http://localhost:8080"
ADMIN_USERNAME = "admin"
ADMIN_PASSWORD = "admin"

@pytest.fixture(scope="module")
def http_client():
    with httpx.Client() as client:
        # Load the login page
        response = client.get(f"{BASE_URL}/login.php")
        assert response.status_code == 200

        # Parse the HTML content
        tree = html.fromstring(response.text)

        # Check if the form elements exist
        assert tree.xpath('//input[@id="user"]')
        assert tree.xpath('//input[@id="password"]')
    
        # Login with the given credentials
        login_data = {
            "login": ADMIN_USERNAME,
            "password": ADMIN_PASSWORD
        }
        login_response = client.post(f"{BASE_URL}/login.php", data=login_data)

        # Ensure the login was successful
        assert login_response.status_code == 302
        yield client  # This client will be used in the tests and it is already authenticated


def test_add_event_missing_csrf(http_client):
    # Load the edit_entry page
    response = http_client.get(f"{BASE_URL}/edit_entry.php")
    assert response.status_code == 200

    # Parse the HTML content
    tree = html.fromstring(response.text)

    # Check if the form elements exist
    assert tree.xpath('//input[@id="entry_brief"]')
    assert tree.xpath('//input[@id="_YMD"]')
    
    # Set the description and date (20091025)
    current_date = datetime.now()
    formatted_date = current_date.strftime('%Y%m%d')
    login_data = {
        "_YMD": formatted_date,
        "name": "Sample Event #1"
    }
    add_response = http_client.post(f"{BASE_URL}/edit_entry_handler.php", data=login_data)

    # Successful save will give 302 redirect to month.php
    assert add_response.status_code == 200 # which means that save failed
    assert "Invalid form request" in add_response.text
    print(add_response.text)
    # TODO: Add additional cases that should fail: Missing brief descriptions, invalid dates, permission denied,
    # invalid or missing timetype, etc.


def test_add_event(http_client):
    # Load the edit_entry page
    response = http_client.get(f"{BASE_URL}/edit_entry.php")
    assert response.status_code == 200

    # Parse the HTML content
    tree = html.fromstring(response.text)

    # Check if the form elements exist
    assert tree.xpath('//input[@id="entry_brief"]')
    assert tree.xpath('//input[@id="_YMD"]')
    csrf_token = tree.xpath('//input[@name="csrf_form_key"]/@value')[0]
    assert csrf_token

    # Set the description and date (20091025)
    current_date = datetime.now()
    formatted_date = current_date.strftime('%Y%m%d')
    # Generate a random UUID
    event_uuid = str(uuid.uuid4())
    event_name = "Event " + event_uuid
    login_data = {
        "csrf_form_key": csrf_token,
        "_YMD": formatted_date,
        "name": event_name,
        "timetype": "U"
    }
    add_response = http_client.post(f"{BASE_URL}/edit_entry_handler.php", data=login_data)

    # Successful save will give 302 redirect to month.php
    assert add_response.status_code == 302
    print(add_response.text)

def test_get_event(http_client):
    # Now use activity_log.php to see if the event shows up
    response = http_client.get(f"{BASE_URL}/activity_log.php?1")
    content = response.text
    #print(response.text)
    # Parse the HTML
    tree = html.fromstring(content)
    url_list = tree.xpath('//a[starts-with(@title, "Event ")]/@href')
    assert url_list
    assert len(url_list) >= 1
    assert url_list
    assert "view_entry.php" in url_list[0]
    #print("url_list: " + str(url_list))
    url=url_list[0]

    # Load the page now
    response = http_client.get(f"{BASE_URL}/{url}")
    assert response.status_code == 200
    content = response.text
    print(content)
    assert 'id="view-event-title"' in content
    assert '<body id="viewentry">' in content
    #TODO: verify content of event details
