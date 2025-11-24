FROM maven:3.9.2-eclipse-temurin-17 AS build
WORKDIR /app
COPY pom.xml .
COPY src ./src
RUN mvn -q -DskipTests package

FROM eclipse-temurin:17-jre
WORKDIR /app

COPY --from=build /app/target/*.jar /app/app.jar
COPY config/firebase_credentials.json /app/config/firebase_credentials.json

ENV FIREBASE_PROJECT_ID=reportes-microservicio

EXPOSE 8080
CMD ["java", "-jar", "app.jar"]
