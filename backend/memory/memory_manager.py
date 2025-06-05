import logging
from typing import List, Dict, Any
from backend.memory.conversation_history_manager import ConversationHistoryManager
# Import other memory types like PersistentKnowledgeStore, TaskContextManager later if needed

logger = logging.getLogger(__name__)

class MemoryManager:
    def __init__(self):
        # Initialize different types of memory stores
        # In a real app, max_history_length might come from config
        self.conversation_history = ConversationHistoryManager(max_history_length=20)
        # self.persistent_knowledge = PersistentKnowledgeStore() # Example for future
        # self.task_context = TaskContextManager() # Example for future
        logger.info("MemoryManager initialized with ConversationHistoryManager.")

    def append_user_message(self, user_id: str, message: str) -> None:
        """Appends a user message to the conversation history."""
        if not user_id or not isinstance(user_id, str): # Basic validation
            logger.warning(f"User ID must be a non-empty string. Received: {user_id}")
            return
        if not message or not isinstance(message, str): # Basic validation
            logger.warning(f"Message must be a non-empty string. Received for user_id '{user_id}': {message}")
            return
        self.conversation_history.append_message(user_id, "user", message)
        logger.debug(f"User message appended for user_id '{user_id}'.")

    def append_assistant_message(self, user_id: str, message: str) -> None:
        """Appends an assistant message to the conversation history."""
        if not user_id or not isinstance(user_id, str): # Basic validation
            logger.warning(f"User ID must be a non-empty string. Received: {user_id}")
            return
        if not message or not isinstance(message, str): # Basic validation
            logger.warning(f"Message must be a non-empty string. Received for user_id '{user_id}': {message}")
            return
        self.conversation_history.append_message(user_id, "assistant", message)
        logger.debug(f"Assistant message appended for user_id '{user_id}'.")

    def get_conversation_context(self, user_id: str, last_n: int = 10) -> List[Dict[str, str]]:
        """
        Retrieves the conversation history for a user, optionally limited to the last N messages.
        """
        if not user_id or not isinstance(user_id, str): # Basic validation
            logger.warning(f"User ID must be a non-empty string. Received: {user_id}")
            return []

        if not isinstance(last_n, int) or last_n < 0:
            logger.warning(f"last_n must be a non-negative integer. Received: {last_n}. Defaulting to 10.")
            last_n = 10 # Default to a sensible value if invalid input

        history = self.conversation_history.get_history(user_id)

        # Only take last_n if last_n is positive and history is longer than last_n
        if last_n > 0 and len(history) > last_n:
            logger.debug(f"Returning last {last_n} messages for user_id '{user_id}' from history of length {len(history)}.")
            return history[-last_n:]

        logger.debug(f"Returning all {len(history)} messages for user_id '{user_id}'.")
        return history

    def clear_user_conversation_history(self, user_id: str) -> None:
        """Clears the conversation history for a specific user."""
        if not user_id or not isinstance(user_id, str): # Basic validation
            logger.warning(f"User ID must be a non-empty string. Received: {user_id}")
            return
        self.conversation_history.clear_history(user_id)
        # logger.info(f"Cleared conversation history for user_id '{user_id}'.") # Logged in CHM

if __name__ == '__main__':
    logging.basicConfig(level=logging.DEBUG)
    mm = MemoryManager()

    test_user = "test_user_001"
    mm.append_user_message(test_user, "First message from user")
    mm.append_assistant_message(test_user, "First response from assistant")
    mm.append_user_message(test_user, "Second message from user")

    context = mm.get_conversation_context(test_user)
    print(f"Context for {test_user}: {context}")
    assert len(context) == 3, f"Expected 3, got {len(context)}"
    assert context[0]["content"] == "First message from user", f"Expected 'First message from user', got {context[0]['content']}"

    limited_context = mm.get_conversation_context(test_user, last_n=1)
    print(f"Limited context for {test_user} (last 1): {limited_context}")
    assert len(limited_context) == 1, f"Expected 1, got {len(limited_context)}"
    assert limited_context[0]["content"] == "Second message from user", f"Expected 'Second message from user', got {limited_context[0]['content']}"

    # Test with invalid last_n, should default to returning all available (up to ConversationHistoryManager's own max if that were smaller than 10)
    # but here, it means it will return all 3 messages as last_n defaults to 10 internally in get_conversation_context if invalid
    invalid_n_context = mm.get_conversation_context(test_user, last_n=-5)
    print(f"Context for {test_user} with invalid last_n (-5), should default to 10 (all 3 here): {invalid_n_context}")
    assert len(invalid_n_context) == 3, f"Expected 3 (defaulted from invalid), got {len(invalid_n_context)}"

    # Test get_conversation_context with last_n = 0 (should return all messages)
    all_context = mm.get_conversation_context(test_user, last_n=0)
    print(f"Context for {test_user} with last_n=0 (all 3 here): {all_context}")
    assert len(all_context) == 3, f"Expected 3 (last_n=0 means all), got {len(all_context)}"

    mm.clear_user_conversation_history(test_user)
    print(f"Context for {test_user} after clearing: {mm.get_conversation_context(test_user)}")
    assert len(mm.get_conversation_context(test_user)) == 0, f"Expected 0, got {len(mm.get_conversation_context(test_user))}"
