FROM python:3.13-slim

WORKDIR /app

COPY src ./src
COPY tests ./tests

ENV PYTHONPATH=/app

CMD ["python", "-m", "src.cli"]
