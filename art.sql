-- user ID - primary key auto increment
CREATE DATABASE IF NOT EXISTS Art_Gallz;
USE Art_Gallz;


CREATE TABLE Users (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100),
    Email VARCHAR(100),
    Password VARCHAR(100),
);
-- artwork ID - primary key auto increment -- tied to user ID as foreign key
-- title - string
-- description - string
-- creation date - date
-- likes counter - integer
-- artwork image URL - string
CREATE TABLE Artworks (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    UserID INT,
    Title VARCHAR(100),
    Description TEXT,
    CreationDate DATE,
    LikesCounter INT DEFAULT 0,
    ImageURL VARCHAR(255),
    FOREIGN KEY (UserID) REFERENCES Users(ID)
);