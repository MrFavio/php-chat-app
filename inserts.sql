INSERT INTO `users` (`public_id`, `username`, `email`, `password`, `code`, `theme`) VALUES
 (0x57e162ca670311f18147345a60562388, 'Test', 'test@example.com', '$2y$10$HF/ID5eZAOv6yWv2Ddz6ueqFU5tRMC6gwqiEzwi43N7UvVQzbt3Rm', '123456', 'blue'),
 (0xac037b21670311f18147345a60562388, 'Kasia', 'kasia.szucka@example.com', '$2y$10$HF/ID5eZAOv6yWv2Ddz6ueqFU5tRMC6gwqiEzwi43N7UvVQzbt3Rm', '654321', 'blue');

INSERT INTO `friends` (`user_one`, `user_two`) VALUES
 (1, 2);