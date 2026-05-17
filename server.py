import json
import os
from http.server import ThreadingHTTPServer, SimpleHTTPRequestHandler
from urllib.parse import urlparse, unquote
from datetime import datetime

# Correct local address is 127.0.0.1
# Do not use 172.0.0.1
HOST = "127.0.0.1"
PORT = 3000

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DATA_FILE = os.path.join(BASE_DIR, "userdata.json")

MALE_IMAGE = "https://cdn-icons-png.flaticon.com/512/3135/3135715.png"
FEMALE_IMAGE = "https://cdn-icons-png.flaticon.com/512/4140/4140047.png"


def ensure_data_file():
    if not os.path.exists(DATA_FILE):
        with open(DATA_FILE, "w", encoding="utf-8") as file:
            json.dump([], file, indent=2)

    try:
        with open(DATA_FILE, "r", encoding="utf-8") as file:
            data = json.load(file)

        if not isinstance(data, list):
            raise ValueError("userdata.json must contain a JSON list")

    except Exception:
        with open(DATA_FILE, "w", encoding="utf-8") as file:
            json.dump([], file, indent=2)


def read_users():
    ensure_data_file()

    with open(DATA_FILE, "r", encoding="utf-8") as file:
        return json.load(file)


def save_users(users):
    with open(DATA_FILE, "w", encoding="utf-8") as file:
        json.dump(users, file, indent=2)


def normalize_email(email):
    return str(email).strip().lower()


def normalize_username(username):
    return str(username).strip().lower()


def today_joined_date():
    return datetime.now().strftime("%d %b %Y")


class AppHandler(SimpleHTTPRequestHandler):
    def end_headers(self):
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type")
        super().end_headers()

    def do_OPTIONS(self):
        self.send_response(200)
        self.end_headers()

    def send_json(self, status_code, data):
        response = json.dumps(data).encode("utf-8")

        self.send_response(status_code)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(response)))
        self.end_headers()

        self.wfile.write(response)

    def get_body_json(self):
        length = int(self.headers.get("Content-Length", 0))

        if length == 0:
            return {}

        body = self.rfile.read(length).decode("utf-8")

        try:
            return json.loads(body)
        except json.JSONDecodeError:
            return {}

    def do_GET(self):
        parsed = urlparse(self.path)
        path = unquote(parsed.path)

        if path == "/":
            self.path = "/login.html"
            return SimpleHTTPRequestHandler.do_GET(self)

        if path == "/profile":
            self.path = "/LoginSucces.html"
            return SimpleHTTPRequestHandler.do_GET(self)

        if path == "/home":
            self.path = "/home.html"
            return SimpleHTTPRequestHandler.do_GET(self)

        if path == "/api/users":
            return self.send_json(200, read_users())

        if path.startswith("/api/profile/"):
            email = normalize_email(path.replace("/api/profile/", ""))
            users = read_users()

            user = next(
                (
                    u for u in users
                    if normalize_email(u.get("gmail", "")) == email
                ),
                None
            )

            if not user:
                return self.send_json(404, {
                    "success": False,
                    "message": "User not found."
                })

            safe_user = user.copy()
            safe_user.pop("password", None)

            return self.send_json(200, {
                "success": True,
                "user": safe_user
            })

        return SimpleHTTPRequestHandler.do_GET(self)

    def do_POST(self):
        parsed = urlparse(self.path)
        path = parsed.path
        data = self.get_body_json()

        if path == "/api/register":
            username = str(data.get("username", "")).strip()
            gmail = normalize_email(data.get("gmail", data.get("email", "")))
            password = str(data.get("password", "")).strip()
            confirm_password = str(
                data.get("confirmPassword", data.get("confirm_password", ""))
            ).strip()

            if not username or not gmail or not password or not confirm_password:
                return self.send_json(400, {
                    "success": False,
                    "message": "All fields are required."
                })

            if password != confirm_password:
                return self.send_json(400, {
                    "success": False,
                    "message": "Password and confirm password do not match."
                })

            users = read_users()

            username_exists = any(
                normalize_username(user.get("username", "")) == normalize_username(username)
                for user in users
            )

            if username_exists:
                return self.send_json(409, {
                    "success": False,
                    "message": "Username already exists."
                })

            gmail_exists = any(
                normalize_email(user.get("gmail", "")) == gmail
                for user in users
            )

            if gmail_exists:
                return self.send_json(409, {
                    "success": False,
                    "message": "Gmail already exists."
                })

            new_user = {
                "fullName": username,
                "username": username,
                "gmail": gmail,
                "password": password,
                "joinedDate": today_joined_date(),
                "gender": "Male",
                "profilePicture": MALE_IMAGE,
                "balance": 0.00
            }

            users.append(new_user)
            save_users(users)

            safe_user = new_user.copy()
            safe_user.pop("password", None)

            return self.send_json(201, {
                "success": True,
                "message": "Registration successful.",
                "user": safe_user
            })

        if path == "/api/login":
            gmail = normalize_email(data.get("gmail", data.get("email", "")))
            password = str(data.get("password", "")).strip()

            users = read_users()

            user = next(
                (
                    u for u in users
                    if normalize_email(u.get("gmail", "")) == gmail
                    and str(u.get("password", "")) == password
                ),
                None
            )

            if not user:
                return self.send_json(401, {
                    "success": False,
                    "message": "Invalid Gmail or password."
                })

            safe_user = user.copy()
            safe_user.pop("password", None)

            return self.send_json(200, {
                "success": True,
                "message": "Login successful.",
                "user": safe_user
            })

        if path == "/api/update-profile":
            old_gmail = normalize_email(
                data.get("oldGmail", data.get("currentGmail", ""))
            )
            full_name = str(data.get("fullName", "")).strip()
            username = str(data.get("username", "")).strip()
            gmail = normalize_email(data.get("gmail", data.get("email", "")))
            gender = str(data.get("gender", "Male")).strip()
            profile_picture = str(data.get("profilePicture", "")).strip()

            if not old_gmail or not full_name or not username or not gmail:
                return self.send_json(400, {
                    "success": False,
                    "message": "Full name, username, and Gmail are required."
                })

            if gender not in ["Male", "Female"]:
                gender = "Male"

            users = read_users()

            user_index = next(
                (
                    index for index, user in enumerate(users)
                    if normalize_email(user.get("gmail", "")) == old_gmail
                ),
                None
            )

            if user_index is None:
                return self.send_json(404, {
                    "success": False,
                    "message": "User not found."
                })

            for index, user in enumerate(users):
                if index == user_index:
                    continue

                if normalize_username(user.get("username", "")) == normalize_username(username):
                    return self.send_json(409, {
                        "success": False,
                        "message": "Username already exists."
                    })

                if normalize_email(user.get("gmail", "")) == gmail:
                    return self.send_json(409, {
                        "success": False,
                        "message": "Gmail already exists."
                    })

            if not profile_picture:
                profile_picture = FEMALE_IMAGE if gender == "Female" else MALE_IMAGE

            users[user_index]["fullName"] = full_name
            users[user_index]["username"] = username
            users[user_index]["gmail"] = gmail
            users[user_index]["gender"] = gender
            users[user_index]["profilePicture"] = profile_picture

            save_users(users)

            safe_user = users[user_index].copy()
            safe_user.pop("password", None)

            return self.send_json(200, {
                "success": True,
                "message": "Profile updated successfully.",
                "user": safe_user
            })

        if path == "/api/add-money":
            gmail = normalize_email(data.get("gmail", data.get("email", "")))
            amount = data.get("amount", 0)

            try:
                amount = float(amount)
            except (ValueError, TypeError):
                amount = 0

            if amount <= 0:
                return self.send_json(400, {
                    "success": False,
                    "message": "Please enter a valid amount."
                })

            users = read_users()

            user_index = next(
                (
                    index for index, user in enumerate(users)
                    if normalize_email(user.get("gmail", "")) == gmail
                ),
                None
            )

            if user_index is None:
                return self.send_json(404, {
                    "success": False,
                    "message": "User not found."
                })

            old_balance = float(users[user_index].get("balance", 0))
            users[user_index]["balance"] = round(old_balance + amount, 2)

            save_users(users)

            safe_user = users[user_index].copy()
            safe_user.pop("password", None)

            return self.send_json(200, {
                "success": True,
                "message": "Money added successfully.",
                "user": safe_user
            })

        return self.send_json(404, {
            "success": False,
            "message": "API route not found."
        })


if __name__ == "__main__":
    ensure_data_file()
    os.chdir(BASE_DIR)

    print("=" * 60)
    print("90N.GameShop server is running")
    print(f"Open this link: http://{HOST}:{PORT}")
    print("Correct local IP is 127.0.0.1, not 172.0.0.1")
    print("Press CTRL + C to stop the server")
    print("=" * 60)

    try:
        server = ThreadingHTTPServer((HOST, PORT), AppHandler)
        server.serve_forever()

    except OSError as error:
        print("")
        print("Server failed to start.")
        print("Possible reason: port 3000 is already being used.")
        print("Close the old server window or change PORT = 3000 to PORT = 8000.")
        print("Error:", error)