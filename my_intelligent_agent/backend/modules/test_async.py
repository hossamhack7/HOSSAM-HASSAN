import asyncio
import logging

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

async def simple_task():
    logger.info("Simple task started.")
    await asyncio.sleep(0.1) # Minimal await
    logger.info("Simple task finished.")
    return "Async Hello World"

async def main():
    logger.info("Main async started.")
    result = await simple_task()
    logger.info(f"Result: {result}")
    logger.info("Main async finished.")

if __name__ == "__main__":
    logger.info("Starting asyncio test script.")
    asyncio.run(main())
    logger.info("Asyncio test script finished.")
