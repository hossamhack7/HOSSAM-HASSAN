import os
import json
from pathlib import Path
from dotenv import load_dotenv

# Load environment variables from .env file
# Construct a path to the .env file in the project root (assuming config.py is in backend/)
env_path = Path(__file__).resolve().parent.parent / '.env'
load_dotenv(dotenv_path=env_path)

# Logging Configuration
LOG_LEVEL = os.getenv("LOG_LEVEL", "INFO").upper()

# Gemini API Configuration
GEMINI_API_KEY = os.getenv("GEMINI_API_KEY")
GEMINI_MODEL_NAME = os.getenv("GEMINI_MODEL_NAME", "gemini-1.5-flash-latest")

_gemini_temp_str = os.getenv("GEMINI_DEFAULT_TEMPERATURE", "0.7")
try:
    GEMINI_DEFAULT_TEMPERATURE = float(_gemini_temp_str)
except ValueError:
    print(f"Warning: Invalid value for GEMINI_DEFAULT_TEMPERATURE: '{_gemini_temp_str}'. Defaulting to 0.7.")
    GEMINI_DEFAULT_TEMPERATURE = 0.7


# Server Configuration
FLASK_RUN_PORT = int(os.getenv("FLASK_RUN_PORT", 5000))

# Agent Configuration
MCP_MODEL_ID = os.getenv("MCP_MODEL_ID", "mcp-agent-v1")

# Filesystem Tool Configuration (Example - to be refined later)
DOCKER_FS_IMAGE_NAME = os.getenv("DOCKER_FS_IMAGE_NAME", "my-fs-tool:latest") # Placeholder

_allowed_mounts_json = os.getenv("ALLOWED_MOUNTS_CONFIG_JSON", '{}')
try:
    ALLOWED_MOUNTS_CONFIG = json.loads(_allowed_mounts_json)
except json.JSONDecodeError:
    print(f"Warning: Invalid JSON in ALLOWED_MOUNTS_CONFIG_JSON: '{_allowed_mounts_json}'. Defaulting to empty config.")
    ALLOWED_MOUNTS_CONFIG = {}


# Self-Development Configuration (Placeholders - to be refined later)
SAVE_SUCCESSFUL_SANDBOX_OUTPUT = os.getenv("SAVE_SUCCESSFUL_SANDBOX_OUTPUT", "false").lower() == "true"
SELF_DEV_OUTPUT_DIR = os.getenv("SELF_DEV_OUTPUT_DIR", "agent_outputs/self_dev_successful")


def print_config_diagnostics():
    print("---- Configuration Diagnostics ----")
    print(f"LOG_LEVEL: {LOG_LEVEL}")
    print(f"GEMINI_API_KEY Loaded: {'Yes' if GEMINI_API_KEY else 'No'}")
    print(f"GEMINI_MODEL_NAME: {GEMINI_MODEL_NAME}")
    print(f"GEMINI_DEFAULT_TEMPERATURE: {GEMINI_DEFAULT_TEMPERATURE}")
    print(f"FLASK_RUN_PORT: {FLASK_RUN_PORT}")
    print(f"MCP_MODEL_ID: {MCP_MODEL_ID}")
    print(f"DOCKER_FS_IMAGE_NAME: {DOCKER_FS_IMAGE_NAME}")
    print(f"ALLOWED_MOUNTS_CONFIG: {ALLOWED_MOUNTS_CONFIG}")
    print(f"SAVE_SUCCESSFUL_SANDBOX_OUTPUT: {SAVE_SUCCESSFUL_SANDBOX_OUTPUT}")
    print(f"SELF_DEV_OUTPUT_DIR: {SELF_DEV_OUTPUT_DIR}")
    print("---------------------------------")

if __name__ == "__main__":
    # This allows running config.py directly to check loaded values
    print_config_diagnostics()
