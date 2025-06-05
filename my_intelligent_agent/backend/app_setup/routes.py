from flask import current_app as app # Using current_app proxy
from backend.app_setup.api_handlers import models_handler, chat_completion_handler

# If using Blueprints:
# from flask import Blueprint
# bp = Blueprint('api_v1', __name__, url_prefix='/v1')
# @bp.route('/models', methods=['GET']) ...

@app.route('/v1/models', methods=['GET'])
def get_models():
    return models_handler.handle_get_models()

@app.route('/v1/chat/completions', methods=['POST', 'OPTIONS'])
def chat_completions():
    if app.request.method == 'OPTIONS':
        # Handle preflight CORS request
        # Flask-CORS should handle this, but explicit handling can be added if needed
        return {}, 200
    return chat_completion_handler.handle_chat_completions()

# Ensure routes are registered when this module is imported by flask_app.py
# If not using blueprints, the @app.route decorator registers them on import
# as long as 'app' here refers to the Flask app instance being configured.
# By using 'from flask import current_app as app', we ensure that
# these routes are associated with the application context active during flask_app.py's import.
