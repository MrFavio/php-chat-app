CREATE TABLE `users` (
  `user_id`   int(11)     PRIMARY KEY AUTO_INCREMENT,
  `username`  varchar(40) NOT NULL,
  `email`     varchar(50) NOT NULL,
  `password`  text        NOT NULL,
  `code`      varchar(6)  NOT NULL,
  `theme`     enum('blue', 'orange', 'yellow', 'violet', 'green') NOT NULL DEFAULT 'blue',
  `img_id`    text        DEFAULT NULL
);

CREATE TABLE `messages` (
  `mess_id`       int(11)   PRIMARY KEY AUTO_INCREMENT,
  `content`       text      NOT NULL,
  `created_at`    datetime  NOT NULL,
  `user_id_from`  int(11)   NOT NULL,
  `user_id_to`    int(11)   NOT NULL,

  FOREIGN KEY (`user_id_from`) REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`user_id_to`)   REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `friends` (
  `friend_id` int(11)   PRIMARY KEY AUTO_INCREMENT,
  `user_one`  int(11)   NOT NULL,
  `user_two`  int(11)   NOT NULL,

  FOREIGN KEY (`user_one`) REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`user_two`)   REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
);