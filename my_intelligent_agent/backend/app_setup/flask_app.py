from flask import Flask
from flask_cors import CORS
from werkzeug.middleware.dispatcher import DispatcherMiddleware # For WSGIMiddleware in main.py
from werkzeug.wrappers import Request # For WSGIMiddleware in main.py


def create_app():
    _flask_app = Flask(__name__)

    # Configure CORS
    CORS(_flask_app, resources={r"/v1/*": {"origins": "*"}}) # Allow all origins for /v1/*

    # Import and register routes and error handlers
    # These imports need to happen after _flask_app is created to avoid circular dependencies
    # and to ensure blueprints (if used) or route decorators are applied to this app instance.
    with _flask_app.app_context():
        from backend.app_setup import routes
        from backend.app_setup import error_handlers

        # If using blueprints, they would be registered here:
        # _flask_app.register_blueprint(routes.bp)
        # _flask_app.register_blueprint(error_handlers.bp)

    # This is a simple WSGI app. We'll wrap it for ASGI in main.py
    # For Uvicorn, the WSGIMiddleware expects a WSGI app.
    # If we were using Flask's built-in server for development (not recommended for async),
    # we would just return _flask_app.
    # However, Uvicorn with WSGIMiddleware is more robust for future async needs.

    # The actual app served by Uvicorn will be the WSGIMiddleware-wrapped version of _flask_app
    # created in main.py. Here, we just return the core Flask app that WSGIMiddleware will wrap.
    return _flask_app

# The Uvicorn command in main.py will point to 'backend.main:flask_app_instance'
# where flask_app_instance is the result of create_app()
