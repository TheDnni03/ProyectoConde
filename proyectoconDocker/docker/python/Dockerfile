FROM python:3.11-slim

WORKDIR /usr/src/app

# Instalar paquetes del sistema si son necesarios
RUN apt-get update && apt-get install -y gcc libmariadb-dev-compat libmariadb-dev && rm -rf /var/lib/apt/lists/*

COPY python-app/requirements.txt ./
RUN pip install --no-cache-dir -r requirements.txt

COPY python-app/ ./

ENV FLASK_APP=app.py
ENV FLASK_RUN_HOST=0.0.0.0

EXPOSE 5000

CMD ["flask", "run"]
