FROM eclipse-temurin:17-jdk-alpine AS build
WORKDIR /workspace
# Si quieres compilar dentro del contenedor, copia pom y src y ejecuta mvn package.
# Aquí asumimos que ya tienes un jar en java-app/target/app.jar

FROM eclipse-temurin:17-jre-alpine
WORKDIR /usr/app
COPY java-app/target/*.jar app.jar
EXPOSE 8080
ENTRYPOINT ["java","-jar","/usr/app/app.jar"]
