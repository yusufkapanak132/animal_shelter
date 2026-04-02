-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Време на генериране:  1 апр 2026 в 11:41
-- Версия на сървъра: 10.4.32-MariaDB
-- Версия на PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данни: `animal_shelter`
--

-- --------------------------------------------------------

--
-- Структура на таблица `adoptions`
--

CREATE TABLE `adoptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `animal_name` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `other_pets` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('Изчаква се','Завършена','Отказана','Потвърдена') DEFAULT 'Изчаква се',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Схема на данните от таблица `adoptions`
--

INSERT INTO `adoptions` (`id`, `user_id`, `animal_name`, `full_name`, `email`, `phone`, `other_pets`, `message`, `status`, `submitted_at`) VALUES
(25, 1, 'Мия', 'Администратор', 'admin@gmail.com', '0893733552', '', 'cxz', 'Изчаква се', '2026-04-01 09:36:48');

-- --------------------------------------------------------

--
-- Структура на таблица `animals`
--

CREATE TABLE `animals` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `type` enum('куче','коте','папагал','заек','хамстер') NOT NULL,
  `age` varchar(20) DEFAULT NULL,
  `gender` enum('Мъжки','Женски') NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `status` enum('Налично','Осиновено','Запазено') DEFAULT 'Налично',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Схема на данните от таблица `animals`
--

INSERT INTO `animals` (`id`, `name`, `type`, `age`, `gender`, `description`, `image_url`, `status`, `created_at`) VALUES
(1, 'Арчи', 'куче', '2 години', 'Мъжки', 'Енергичен и игрив, обича топки.', 'assets/images/animals_1.avif', 'Налично', '2026-01-26 17:12:20'),
(2, 'Луна', 'коте', '1 година', 'Женски', 'Спокойна и гальовна, идеална за апартамент.', 'assets/images/animals_2.avif', 'Налично', '2026-01-26 17:12:20'),
(3, 'Бъди', 'куче', '5 години', 'Мъжки', 'Мъдър и верен пазач на дома.', 'assets/images/animals_3.avif', 'Налично', '2026-01-26 17:12:20'),
(4, 'Мия', 'коте', '3 месеца', 'Женски', 'Малко коте, търсещо любов.', 'assets/images/animals_4.avif', 'Налично', '2026-01-26 17:12:20'),
(5, 'Роки', 'куче', '3 години', 'Мъжки', 'Обича дългите разходки в планината.', 'assets/images/animals_5.avif', 'Налично', '2026-01-26 17:12:20'),
(6, 'Джеси', 'куче', '4 години', 'Женски', 'Много социална, разбира се с други кучета.', 'assets/images/animals_6.avif', 'Налично', '2026-03-26 17:12:20'),
(7, 'Кико', 'папагал', '2 години', 'Мъжки', 'Шумен и любопитен, обича да имитира звуци и да общува с хора.', 'assets/images/animals_7.avif', 'Налично', '2026-03-16 10:38:03'),
(8, 'Снежи', 'заек', '1 година', 'Женски', 'Спокойна и пухкава, обича моркови и тихи места за почивка.', 'assets/images/animals_8.avif', 'Налично', '2026-03-18 16:09:23');

-- --------------------------------------------------------

--
-- Структура на таблица `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `adoption_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('Планирано','Завършено','Отказано') DEFAULT 'Планирано',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Схема на данните от таблица `appointments`
--

INSERT INTO `appointments` (`id`, `adoption_id`, `user_id`, `appointment_date`, `appointment_time`, `status`, `created_at`) VALUES
(24, 25, 1, '2026-04-09', '15:00:00', 'Планирано', '2026-04-01 09:37:20');

-- --------------------------------------------------------

--
-- Структура на таблица `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('Ново','Прочетено','Отговорено') DEFAULT 'Ново',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура на таблица `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` enum('Изчаква се','Завършено','Отказано') DEFAULT 'Изчаква се',
  `donation_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Схема на данните от таблица `donations`
--

INSERT INTO `donations` (`id`, `user_id`, `amount`, `full_name`, `email`, `payment_method`, `status`, `donation_date`) VALUES
(10, 2, 50.00, 'Юсуф Капанък', 'yusuf.kapanak@pmggd.bg', 'Stripe', 'Завършено', '2026-03-18 21:36:25'),
(11, 2, 50.00, 'Юсуф Капанък', 'yusuf.kapanak@pmggd.bg', 'Stripe', 'Завършено', '2026-03-18 21:37:24'),
(12, 2, 50.00, 'Юсуф Капанък', 'yusuf.kapanak@pmggd.bg', 'Stripe', 'Отказано', '2026-03-20 04:45:37');

-- --------------------------------------------------------

--
-- Структура на таблица `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('Непотвърдена','Изпратена','Чакаща','Обработва се','Завършена','Отказана') DEFAULT 'Непотвърдена',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Схема на данните от таблица `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `full_name`, `email`, `phone`, `address`, `total_amount`, `status`, `order_date`) VALUES
(8, NULL, 'Юсуф Капанък', 'yusuf.kapanak@pmggd.bg', '0893733552', 'Панайот Хитов 4, Село Брезница, 2972', 45.00, 'Чакаща', '2026-04-01 03:25:16'),
(9, NULL, 'Юсуф Капанък', 'yusuf.kapanak@pmggd.bg', '0893733552', 'Панайот Хитов 4, Село Брезница, 2972', 45.00, 'Чакаща', '2026-04-01 03:25:50'),
(11, 2, 'Юсуф Капанък', 'yusuf.kapanak@pmggd.bg', '0893733552', 'sad', 20.00, 'Чакаща', '2026-04-01 05:55:51'),
(12, 2, 'Юсуф Капанък', 'yusuf.kapanak@pmggd.bg', '0893733552', 'dsa', 45.00, 'Завършена', '2026-04-01 06:02:46');

-- --------------------------------------------------------

--
-- Структура на таблица `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Схема на данните от таблица `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `price`) VALUES
(19, 8, 1, 'Удобно легло', 1, 45.00),
(20, 9, 1, 'Удобно легло', 1, 45.00),
(22, 11, 3, 'Кожен нашийник', 1, 20.00),
(23, 12, 1, 'Удобно легло', 1, 45.00);

-- --------------------------------------------------------

--
-- Структура на таблица `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `stock` int(11) DEFAULT 10,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Схема на данните от таблица `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `description`, `image_url`, `stock`, `created_at`) VALUES
(1, 'Удобно легло', 45.00, 'Мек и удобен лежак за вашето коте.', 'assets/images/accessories_1.avif', 10, '2026-01-26 17:12:20'),
(2, 'Премиум храна', 80.00, 'Висококачествена храна с необходимите хранителни вещества за вашето куче.', 'assets/images/accessories_2.avif', 10, '2026-01-26 17:12:20'),
(3, 'Кожен нашийник', 20.00, 'Елегантен кожен нашийник с регулируема дължина.', 'assets/images/accessories_3.avif', 9, '2026-01-26 17:12:20'),
(4, 'Играчка кокал', 12.00, 'Играчка за гризане, безопасна за зъбите.', 'assets/images/accessories_4.avif', 7, '2026-01-26 17:12:20'),
(5, 'Преносна кутия за папагал', 35.00, 'Удобна преносна кутия за пътуване с вашият папагал.', 'assets/images/accessories_5.avif', 7, '2026-01-26 17:12:20'),
(6, 'Котешка катерушка', 60.00, 'Съвременна котешка катерушка със стълб за драскане.', 'assets/images/accessories_6.avif', 10, '2026-01-26 17:12:20'),
(7, 'Дървена клетка за заек', 32.00, 'Красива, уютна и удобна дървена клетка за вашият заек.', 'assets/images/accessories_7.avif', 8, '2026-03-18 20:51:10');

-- --------------------------------------------------------

--
-- Структура на таблица `stray_reports`
--

CREATE TABLE `stray_reports` (
  `id` int(11) NOT NULL,
  `animal_type` varchar(50) NOT NULL,
  `location_address` varchar(255) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `reporter_name` varchar(100) DEFAULT NULL,
  `reporter_phone` varchar(20) DEFAULT NULL,
  `status` enum('Ново','В процес','Спасено') DEFAULT 'Ново',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Схема на данните от таблица `stray_reports`
--

INSERT INTO `stray_reports` (`id`, `animal_type`, `location_address`, `latitude`, `longitude`, `image_path`, `description`, `reporter_name`, `reporter_phone`, `status`, `created_at`) VALUES
(14, 'друго', 'dsa', 41.57503689, 23.72940840, 'uploads/signals/signal_1.png', 'dasdas', 'Юсуф Капанък', '0893733552', 'Ново', '2026-04-01 06:23:49');

-- --------------------------------------------------------

--
-- Структура на таблица `success_stories`
--

CREATE TABLE `success_stories` (
  `id` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `animal_name` varchar(20) NOT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `status` enum('Не одобрена','Одобрена','','') NOT NULL DEFAULT 'Не одобрена',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Схема на данните от таблица `success_stories`
--

INSERT INTO `success_stories` (`id`, `title`, `description`, `animal_name`, `image_url`, `status`, `created_at`) VALUES
(1, 'Верен приятел', 'Когато отидох в приюта, видях едно куче, което беше малко уплашено и стоеше тихо в ъгъла. Реших да го осиновя и да му дам дом, защото усетих, че има нужда от човек до себе си. В началото беше предпазливо, но с времето започна да ми се доверява и сега всеки ден излизаме на разходки и сме неразделни приятели.', 'Макс', 'assets/images/stories_1.avif', 'Одобрена', '2026-01-26 17:12:20'),
(2, 'Малък Спътник', 'Посетих приюта без да знам дали ще осиновя животно, но едно малко коте веднага привлече вниманието ми. То беше любопитно и тихо, а когато го взех на ръце, разбрах, че искам да му дам дом. Сега живее при мен, играе из къщи и мърка доволно, а домът ми стана много по-жив и весел.', 'Пух', 'assets/images/stories_2.avif', 'Одобрена', '2026-01-26 17:12:20'),
(3, 'Малка Радост', 'Един ден реших да посетя приюта и там видях малък хамстер, който веднага ми стана симпатичен. Реших да го осиновя и да му създам уютно място у дома с клетка, играчки и всичко необходимо. Сега той тича щастливо по колелото си, а аз се радвам, че му дадох нов шанс и сигурен дом.', 'Чочо', 'assets/images/stories_3.avif', 'Одобрена', '2026-01-26 17:12:20'),
(16, 'Рижият приятел', 'Когато посетих приюта, едно рижо коте на име Мъри веднага дойде при мен и започна да се гали в ръката ми. Беше много игриво, но и малко уплашено от новите хора около него. Реших да го осиновя и да му дам дом, а днес Мъри тича из къщи, играе с всичко, което намери, и всяка вечер заспива спокойно до мен.', 'Мъри', 'assets/images/stories_4.avif', 'Одобрена', '2026-03-15 15:55:15'),
(17, 'Меки уши', 'В приюта видях малко бяло зайче на име Снежко с дълги меки уши. То беше спокойно и много любопитно, постоянно подскачаше и разглеждаше всичко около себе си. Реших да го осиновя и сега Снежко има свое място у дома, където спокойно си играе и всеки ден ме посреща с малките си подскоци.', 'Снежко', 'assets/images/stories_5.avif', 'Одобрена', '2026-03-15 15:57:31'),
(18, 'Шарено приятелство', 'Когато минавах покрай клетките, един цветен папагал на име Рико започна да ми говори и да издава забавни звуци. Това веднага ме накара да се усмихна и реших, че искам да му дам нов дом. Сега Рико живее при мен, чурулика весело и дори започна да научава няколко думи, които повтаря всеки ден.', 'Рико', 'assets/images/stories_6.avif', 'Одобрена', '2026-03-15 15:59:44'),
(19, 'Малкото чудо', 'Когато посетих приюта, едно малко хамстерче на име Бъни веднага привлече вниманието ми с игривите си движения и пухкавата опашка. Реших да му дам шанс за живот. Сега Бъни е най-щастливото хамстерче на света!', 'Бъни', 'assets/images/stories_7.avif', 'Одобрена', '2026-03-18 20:49:58');

-- --------------------------------------------------------

--
-- Структура на таблица `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `email_status` varchar(20) NOT NULL DEFAULT 'Не потвърден',
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Потребител','Администратор') DEFAULT 'Потребител',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Схема на данните от таблица `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `email_status`, `phone`, `password`, `role`, `created_at`, `updated_at`, `reset_token`, `reset_token_expires_at`) VALUES
(1, 'Администратор', 'admin@gmail.com', 'Не потвърден', '0893733552', '$2y$10$Qeu1A2y2CO9FVMsk9ZIfiefOuLeMr5beqmXDRGu9yTnxKxvDkQRU2', 'Администратор', '2026-01-26 17:29:19', '2026-03-22 10:31:54', NULL, NULL),
(2, 'Юсуф Капанък', 'yusuf.kapanak@pmggd.bg', 'Потвърден', '0893733552', '$2y$10$.RQliBG1tMQ8r8wGuzE4e.mbs.HvKbzbinAEN5rvEsHgbFLefuMUC', 'Потребител', '2026-03-09 11:34:28', '2026-04-01 05:52:46', '597147', '2026-04-01 09:07:46');

--
-- Indexes for dumped tables
--

--
-- Индекси за таблица `adoptions`
--
ALTER TABLE `adoptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индекси за таблица `animals`
--
ALTER TABLE `animals`
  ADD PRIMARY KEY (`id`);

--
-- Индекси за таблица `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `adoption_id` (`adoption_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индекси за таблица `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Индекси за таблица `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индекси за таблица `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индекси за таблица `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Индекси за таблица `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Индекси за таблица `stray_reports`
--
ALTER TABLE `stray_reports`
  ADD PRIMARY KEY (`id`);

--
-- Индекси за таблица `success_stories`
--
ALTER TABLE `success_stories`
  ADD PRIMARY KEY (`id`);

--
-- Индекси за таблица `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `adoptions`
--
ALTER TABLE `adoptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `animals`
--
ALTER TABLE `animals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `stray_reports`
--
ALTER TABLE `stray_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `success_stories`
--
ALTER TABLE `success_stories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ограничения за дъмпнати таблици
--

--
-- Ограничения за таблица `adoptions`
--
ALTER TABLE `adoptions`
  ADD CONSTRAINT `adoptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения за таблица `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`adoption_id`) REFERENCES `adoptions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения за таблица `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `donations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения за таблица `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения за таблица `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
