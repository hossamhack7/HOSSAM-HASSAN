from flask import jsonify, current_app as app
from backend.config import MCP_MODEL_ID

def handle_get_models():
    # This is to mimic the OpenAI API's /v1/models endpoint
    # for compatibility with Open WebUI or similar clients.
    return jsonify({
        "object": "list",
        "data": [
            {
                "id": MCP_MODEL_ID,
                "object": "model",
                "created": 1677610600, # Example timestamp
                "owned_by": "my-intelligent-agent",
                "permission": [],
                "root": MCP_MODEL_ID,
                "parent": None,
            }
            # Add other "models" if needed, though typically one for the agent
        ]
    })
