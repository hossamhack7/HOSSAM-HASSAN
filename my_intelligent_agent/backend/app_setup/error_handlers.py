from flask import current_app as app, jsonify

# If using Blueprints:
# from flask import Blueprint
# bp = Blueprint('errors', __name__)
# @bp.app_errorhandler(500) ...

@app.errorhandler(500)
def handle_500_error(error):
    app.logger.error(f"Server Error: {error}", exc_info=True)
    response = {
        "error": {
            "message": "An internal server error occurred. Please try again later.",
            "type": "internal_server_error",
            "code": 500
        }
    }
    return jsonify(response), 500

@app.errorhandler(404)
def handle_404_error(error):
    app.logger.info(f"Not Found: {app.request.path}")
    response = {
        "error": {
            "message": "The requested resource was not found.",
            "type": "not_found_error",
            "code": 404
        }
    }
    return jsonify(response), 404

# Ensure error handlers are registered when this module is imported by flask_app.py
