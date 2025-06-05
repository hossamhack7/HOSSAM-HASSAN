import logging
from typing import List, Dict, Any

logger = logging.getLogger(__name__)

class ConversationHistoryManager:
    def __init__(self, max_history_length: int = 20):
        self._histories: Dict[str, List[Dict[str, str]]] = {}
        self.max_history_length = max_history_length
        if self.max_history_length <= 0:
            logger.warning(f"max_history_length is {self.max_history_length}. Setting to a default of 20. It should be a positive integer.")
            self.max_history_length = 20
        logger.info(f"ConversationHistoryManager initialized with max_history_length: {self.max_history_length}")

    def append_message(self, user_id: str, role: str, content: str) -> None:
        if not user_id:
            logger.error("user_id cannot be empty when appending a message.")
            return

        if user_id not in self._histories:
            self._histories[user_id] = []

        self._histories[user_id].append({"role": role, "content": content})
        logger.debug(f"Appended message for user_id '{user_id}': role='{role}'")

        # Trim history if it exceeds max_history_length
        if len(self._histories[user_id]) > self.max_history_length:
            self._histories[user_id] = self._histories[user_id][-self.max_history_length:]
            logger.debug(f"History for user_id '{user_id}' trimmed to last {self.max_history_length} messages.")

    def get_history(self, user_id: str) -> List[Dict[str, str]]:
        if not user_id:
            logger.error("user_id cannot be empty when getting history.")
            return []
        history = self._histories.get(user_id, [])
        logger.debug(f"Retrieved history for user_id '{user_id}'. Length: {len(history)}")
        return list(history) # Return a copy to prevent external modification

    def clear_history(self, user_id: str) -> None:
        if not user_id:
            logger.error("user_id cannot be empty when clearing history.")
            return

        if user_id in self._histories:
            del self._histories[user_id]
            logger.info(f"History cleared for user_id '{user_id}'.")
        else:
            logger.info(f"No history found for user_id '{user_id}' to clear.")

if __name__ == '__main__':
    logging.basicConfig(level=logging.DEBUG)
    chm = ConversationHistoryManager(max_history_length=3)

    user1 = "user_alpha"
    chm.append_message(user1, "user", "Hello!")
    chm.append_message(user1, "assistant", "Hi there!")
    chm.append_message(user1, "user", "How are you?")
    print(f"History for {user1}: {chm.get_history(user1)}")

    chm.append_message(user1, "assistant", "I'm good, thanks! And you?") # This should trim "Hello!"
    print(f"History for {user1} after one more message (should be trimmed): {chm.get_history(user1)}")
    assert len(chm.get_history(user1)) == 3, f"Expected 3, got {len(chm.get_history(user1))}"
    assert chm.get_history(user1)[0]["content"] == "Hi there!", f"Expected 'Hi there!', got {chm.get_history(user1)[0]['content']}"

    user2 = "user_beta"
    chm.append_message(user2, "user", "My first message.")
    print(f"History for {user2}: {chm.get_history(user2)}")
    assert len(chm.get_history(user2)) == 1, f"Expected 1, got {len(chm.get_history(user2))}"

    chm.clear_history(user1)
    print(f"History for {user1} after clearing: {chm.get_history(user1)}")
    assert len(chm.get_history(user1)) == 0, f"Expected 0, got {len(chm.get_history(user1))}"
    print(f"History for {user2} (should be unaffected): {chm.get_history(user2)}")
