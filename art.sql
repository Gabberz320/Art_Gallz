-- user ID - primary key auto increment
CREATE DATABASE IF NOT EXISTS Art_Gallz;
USE Art_Gallz;


CREATE TABLE Users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    oauthID VARCHAR(255) UNIQUE,
    Name VARCHAR(100),
    Email VARCHAR(100)
);
-- artwork ID - primary key auto increment -- tied to user ID as foreign key
-- title - string
-- description - string
-- creation date - date
-- likes counter - integer
-- artwork image URL - string
CREATE TABLE Artworks (
    art_ID INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    Title VARCHAR(100),
    Description TEXT,
    CreationDate DATE,
    LikesCounter INT DEFAULT 0,
    ImageURL VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
);

-- like ID - primary key auto increment -- represents the number of likes
CREATE TABLE IF NOT EXISTS Likes (
    like_id  INT PRIMARY KEY AUTO_INCREMENT,
    user_id  INT,
    art_ID   INT,
    UNIQUE KEY unique_like (user_id, art_ID),
    FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (art_ID)  REFERENCES Artworks(art_ID) ON DELETE CASCADE
);
 
