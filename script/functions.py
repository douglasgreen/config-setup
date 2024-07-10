import os
import re
import subprocess
import sys

def change_to_parent_directory():
    try:
        # Run the Git command to get the root directory of the Git project
        git_root = subprocess.check_output(['git', 'rev-parse', '--show-toplevel'], stderr=subprocess.STDOUT).strip().decode('utf-8')
        os.chdir(git_root)
    except subprocess.CalledProcessError as e:
        print(f"Error: Not inside a Git repository: {e}", file=sys.stderr)
        sys.exit(1)

def install_composer_dependencies():
    if os.path.isfile("composer.json"):
        if not os.path.isdir("vendor"):
            print("==> Installing Composer dependencies...")
            try:
                subprocess.run(["composer", "install", "--no-interaction", "--prefer-dist"], check=True)
            except subprocess.CalledProcessError as e:
                print(f"Error installing Composer dependencies: {e}", file=sys.stderr)
                sys.exit(1)

def install_npm_dependencies():
    if os.path.isfile("package.json"):
        if not os.path.isdir("node_modules"):
            print("==> Installing NPM dependencies...")
            try:
                subprocess.run(["npm", "install", "--silent"], check=True)
            except subprocess.CalledProcessError as e:
                print(f"Error installing NPM dependencies: {e}", file=sys.stderr)
                sys.exit(1)

def run_bootstrap_script():
    try:
        subprocess.run(["script/bootstrap"], check=True)
    except subprocess.CalledProcessError as e:
        print(f"Error running bootstrap script: {e}", file=sys.stderr)
        sys.exit(1)

def run_composer_lint():
    try:
        result = subprocess.run(["composer", "run-script", "--list"], capture_output=True, text=True, check=True)
        if re.search(r'^\s*lint\s+', result.stdout, re.MULTILINE):
            print("==> Running Composer lint...")
            subprocess.run(["composer", "lint"], check=True)
        else:
            print("===> Composer lint script not defined")
    except subprocess.CalledProcessError as e:
        print(f"Error checking or running Composer lint: {e}", file=sys.stderr)
        sys.exit(1)

def run_composer_lint_fix():
    try:
        result = subprocess.run(["composer", "run-script", "--list"], capture_output=True, text=True, check=True)
        if re.search(r'^\s*lint:fix\s+', result.stdout, re.MULTILINE):
            print("==> Running Composer lint:fix...")
            subprocess.run(["composer", "lint:fix"], check=True)
        else:
            print("===> Composer lint:fix script not defined")
    except subprocess.CalledProcessError as e:
        print(f"Error checking or running Composer lint:fix: {e}", file=sys.stderr)
        sys.exit(1)

def run_composer_test():
    try:
        result = subprocess.run(["composer", "run-script", "--list"], capture_output=True, text=True, check=True)
        if re.search(r'^\s*test\s+', result.stdout, re.MULTILINE):
            print("==> Running Composer test...")
            subprocess.run(["composer", "test"], check=True)
        else:
            print("==> Composer test script not defined")
    except subprocess.CalledProcessError as e:
        print(f"Error checking or running Composer test: {e}", file=sys.stderr)
        sys.exit(1)

def run_npm_lint():
    try:
        result = subprocess.run(["npm", "run"], capture_output=True, text=True, check=True)
        if re.search(r'^\s*lint\s*$', result.stdout, re.MULTILINE):
            print("==> Running NPM lint...")
            subprocess.run(["npm", "run", "lint"], check=True)
        else:
            print("===> NPM lint script not defined")
    except subprocess.CalledProcessError as e:
        print(f"Error checking or running NPM lint: {e}", file=sys.stderr)
        sys.exit(1)

def run_npm_lint_fix():
    try:
        result = subprocess.run(["npm", "run"], capture_output=True, text=True, check=True)
        if re.search(r'^\s*lint:fix\s*$', result.stdout, re.MULTILINE):
            print("==> Running NPM lint:fix...")
            subprocess.run(["npm", "run", "lint:fix"], check=True)
        else:
            print("===> NPM lint:fix script not defined")
    except subprocess.CalledProcessError as e:
        print(f"Error checking or running NPM lint:fix: {e}", file=sys.stderr)
        sys.exit(1)

def run_npm_test():
    try:
        result = subprocess.run(["npm", "run"], capture_output=True, text=True, check=True)
        if re.search(r'^\s*test\s*$', result.stdout, re.MULTILINE):
            print("==> Running NPM test...")
            subprocess.run(["npm", "run", "test"], check=True)
        else:
            print("==> NPM test script not defined")
    except subprocess.CalledProcessError as e:
        print(f"Error checking or running NPM test: {e}", file=sys.stderr)
        sys.exit(1)

def setup_database():
    if os.path.isfile("script/setup-db"):
        print("==> Setting up database...")
        try:
            subprocess.run(["script/setup-db"], check=True)
        except subprocess.CalledProcessError as e:
            print(f"Error setting up database: {e}", file=sys.stderr)
            sys.exit(1)

def setup_environment_variables():
    if os.path.isfile(".env"):
        print("==> Setting up environment variables...")
        try:
            with open(".env") as f:
                for line in f:
                    if line.strip() and not line.startswith('#'):
                        key, value = line.strip().split('=', 1)
                        os.environ[key] = value
        except Exception as e:
            print(f"Error setting up environment variables: {e}", file=sys.stderr)
            sys.exit(1)

def set_xdebug_mode():
    os.environ["XDEBUG_MODE"] = "coverage"

def update_database():
    if os.path.isfile("script/update-db"):
        print("==> Updating database...")
        try:
            subprocess.run(["script/update-db"], check=True)
        except subprocess.CalledProcessError as e:
            print(f"Error updating database: {e}", file=sys.stderr)
            sys.exit(1)
