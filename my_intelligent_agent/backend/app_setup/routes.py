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

@app.route('/v1/chat', methods=['POST', 'OPTIONS'])
def wordpress_chat():
    """Simple chat endpoint for WordPress plugin integration"""
    from flask import request, jsonify
    import logging
    
    logger = logging.getLogger(__name__)
    
    if request.method == 'OPTIONS':
        # Handle preflight CORS request
        return {}, 200
    
    try:
        data = request.get_json()
        if not data:
            return jsonify({'error': 'No JSON data provided'}), 400
        
        message = data.get('message', '').strip()
        if not message:
            return jsonify({'error': 'Message is required'}), 400
        
        api_key = data.get('api_key', '')
        
        logger.info(f"WordPress chat request: {message[:50]}...")
        
        # Use the existing chat completion handler but format it for simple response
        # Create a simplified request format
        chat_request = {
            "messages": [
                {
                    "role": "user",
                    "content": message
                }
            ],
            "model": "gemini-1.5-flash-latest",
            "max_tokens": 500,
            "temperature": 0.7
        }
        
        # Temporarily modify the request to use our data
        original_json = request.json
        request.json = chat_request
        
        # Call the existing handler
        response = chat_completion_handler.handle_chat_completions()
        
        # Restore original request
        request.json = original_json
        
        # Extract the response content
        if isinstance(response, tuple):
            response_data, status_code = response
        else:
            response_data = response
            status_code = 200
        
        if status_code == 200 and 'choices' in response_data:
            ai_response = response_data['choices'][0]['message']['content']
            return jsonify({
                'success': True,
                'response': ai_response
            })
        else:
            logger.error(f"Chat completion error: {response_data}")
            return jsonify({
                'success': False,
                'error': 'Failed to get AI response'
            }), 500
            
    except Exception as e:
        logger.error(f"WordPress chat error: {str(e)}")
        return jsonify({
            'success': False,
            'error': 'Internal server error'
        }), 500

# Ensure routes are registered when this module is imported by flask_app.py
# If not using blueprints, the @app.route decorator registers them on import
# as long as 'app' here refers to the Flask app instance being configured.
# By using 'from flask import current_app as app', we ensure that
# these routes are associated with the application context active during flask_app.py's import.
