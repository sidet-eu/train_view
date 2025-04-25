CREATE TABLE train_data (
  StanicaZCislo varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  StanicaDoCislo varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  Nazov varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  TypVlaku varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  CisloVlaku varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  NazovVlaku varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  Popis text COLLATE utf8mb4_general_ci DEFAULT NULL,
  Meska int DEFAULT NULL,
  Dopravca varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  InfoZoStanice varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  MeskaText text COLLATE utf8mb4_general_ci DEFAULT NULL,
  date_added timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
