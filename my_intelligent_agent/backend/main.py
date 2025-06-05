import uvicorn
from backend.config import FLASK_RUN_PORT, LOG_LEVEL, print_config_diagnostics
from backend.app_setup.flask_app import create_app
# For Uvicorn compatibility with WSGI apps (like Flask)
from asgiref.wsgi import WsgiToAsgi

# Setup proper logging using LOG_LEVEL from config
import logging
logging.basicConfig(level=LOG_LEVEL.upper()) # Ensure basicConfig is called before getLogger
logger = logging.getLogger(__name__)

logger.info("Starting My Intelligent Agent...")
print_config_diagnostics() # Print config on startup

# Create the core Flask app instance.
# This also ensures routes and error handlers are registered within its context.
core_flask_app = create_app()

# Wrap the Flask app (WSGI) with WsgiToAsgi to make it an ASGI application
# that Uvicorn can serve.
final_asgi_app = WsgiToAsgi(core_flask_app)

if __name__ == "__main__":
    # It's important that uvicorn.run() targets the ASGI app (final_asgi_app).
    # The string format "module:variable" is used if the app is not directly passed.
    # Since we have 'final_asgi_app' available in this scope, we can pass it directly.
    # However, to enable reload feature reliably, Uvicorn prefers an import string.
    uvicorn.run(
        "backend.main:final_asgi_app", # Path to the ASGI app object
        host="0.0.0.0",
        port=FLASK_RUN_PORT,
        log_level=LOG_LEVEL.lower(), # Uvicorn expects lowercase log level
        reload=True # Enable auto-reload for development
    )
