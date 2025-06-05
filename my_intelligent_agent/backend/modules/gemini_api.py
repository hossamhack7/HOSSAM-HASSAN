import logging
import asyncio
import google.generativeai as genai
from backend import config # Use the alias for clarity if preferred, or direct from backend.config

logger = logging.getLogger(__name__)

class GeminiApiService:
    def __init__(self): # Removed parameters, directly uses imported config
        try:
            if not config.GEMINI_API_KEY:
                logger.error("GEMINI_API_KEY is not configured. GeminiApiService cannot be initialized.")
                raise ValueError("GEMINI_API_KEY not found in configuration.")

            genai.configure(api_key=config.GEMINI_API_KEY)
            self.model = genai.GenerativeModel(model_name=config.GEMINI_MODEL_NAME)
            logger.info(f"GeminiApiService initialized successfully with model: {config.GEMINI_MODEL_NAME}")
        except Exception as e:
            logger.error(f"Error initializing GeminiApiService: {e}", exc_info=True)
            # Propagate the error to ensure service initialization failure is clear
            raise

    async def generate_text_from_prompt(self, prompt: str, temperature: float = None) -> str:
        effective_temperature = temperature if temperature is not None else config.GEMINI_DEFAULT_TEMPERATURE
        logger.info(f"Generating text from prompt (first 80 chars): '{prompt[:80]}...' with temperature: {effective_temperature}")

        try:
            generation_config = genai.types.GenerationConfig(temperature=effective_temperature)

            response = await self.model.generate_content_async(
                prompt,
                generation_config=generation_config
            )

            if response.prompt_feedback.block_reason:
                block_reason_str = str(response.prompt_feedback.block_reason)
                logger.warning(f"Prompt was blocked. Reason: {block_reason_str}")
                return f"Error: Content generation blocked due to {block_reason_str}."

            if not response.candidates:
                logger.warning(f"Gemini API returned no candidates. Full response: {response}")
                return "Error: Gemini API returned no content (no candidates)."

            # Accessing text safely from parts, as response.text might not always be populated directly
            # or might be missing if the response was blocked or empty.
            # Based on Gemini API, content is in candidates[0].content.parts
            if response.candidates[0].content and response.candidates[0].content.parts:
                full_text_response = "".join(part.text for part in response.candidates[0].content.parts if hasattr(part, 'text'))
                logger.debug(f"Successfully received response from Gemini. Response (first 100 chars): {full_text_response[:100]}...")
                return full_text_response.strip()
            else:
                logger.warning(f"Gemini API returned no text parts in the first candidate. Full response: {response}")
                return "Error: Gemini API returned no text content in the response."

        except Exception as e:
            logger.error(f"Error during Gemini API call: {e}", exc_info=True)
            # It's often good to return a more generic error to the caller,
            # but log the specific error for debugging.
            return f"Error: Could not connect to Gemini API or process the request. Please check server logs for details."

if __name__ == '__main__':
    # This block is for basic, direct testing of this module.
    # You'll need to have your .env file correctly set up in the project root.
    # To run this: python -m backend.modules.gemini_api

    # Setup basic logging to see output from this module
    logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(name)s - %(levelname)s - %(message)s')

    # Ensure config is loaded by running this as a module from the project root
    # (or by ensuring backend.config can find .env when this script is run)

    async def test_gemini_service():
        logger.info("Attempting to initialize GeminiApiService for testing...")
        try:
            service = GeminiApiService() # Assumes config is loaded because .env is in root

            prompt1 = "Hello, Gemini! What is the capital of France?"
            logger.info(f"Sending prompt 1: '{prompt1}'")
            response1 = await service.generate_text_from_prompt(prompt1)
            logger.info(f"Response 1: {response1}")

            prompt2 = "Write a short poem about coding."
            logger.info(f"Sending prompt 2: '{prompt2}' with custom temperature 0.9")
            response2 = await service.generate_text_from_prompt(prompt2, temperature=0.9)
            logger.info(f"Response 2: {response2}")

            # Example of a potentially problematic prompt (depending on safety settings)
            # prompt3 = "How do I hotwire a car?"
            # logger.info(f"Sending prompt 3: '{prompt3}'")
            # response3 = await service.generate_text_from_prompt(prompt3)
            # logger.info(f"Response 3: {response3}")

        except ValueError as ve:
            logger.error(f"Test failed during service initialization: {ve}")
        except Exception as e:
            logger.error(f"An error occurred during testing: {e}", exc_info=True)

    if config.GEMINI_API_KEY: # Only run test if API key is likely present
        asyncio.run(test_gemini_service())
    else:
        logger.warning("GEMINI_API_KEY not found in config. Skipping __main__ test for GeminiApiService.")
