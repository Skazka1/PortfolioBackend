-- Выполните от имени суперпользователя postgres (pgAdmin → подключение к серверу → Query Tool, база «postgres»).

CREATE ROLE portfolio WITH LOGIN PASSWORD 'secret';

CREATE DATABASE portfolio OWNER portfolio;

-- Если команда CREATE DATABASE вернула «уже существует», достаточно было только CREATE ROLE — проверьте пароль в .env.
