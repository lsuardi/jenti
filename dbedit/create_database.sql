/*
Copyright 2015 - NINETY-DEGREES

HISTORY

20150417 - user.birthdate
20160105 - user.score ALTER TABLE USER ADD SCORE INT(6);

*/

/*USE JENTI;*/

/*
CREATE TABLE `ENUM` (
  `ID` INT(10) NOT NULL AUTO_INCREMENT,
  `NAME` VARCHAR(30) NOT NULL,
  `SEQUENCE` INT(10) NOT NULL,
  `VALUE` VARCHAR(30) NOT NULL,
  `LANGUAGE_CODE` VARCHAR(10) NOT NULL,
  PRIMARY KEY (`ID`)
);

INSERT INTO ENUM VALUES (100, 'LANGUAGE', 1, 'italiano', 'it');
INSERT INTO ENUM VALUES (101, 'LANGUAGE', 2, 'inglese', 'it');

INSERT INTO ENUM VALUES (200, 'LANGUAGE', 1, 'italian', 'en');
INSERT INTO ENUM VALUES (201, 'LANGUAGE', 2, 'english', 'en');

INSERT INTO ENUM VALUES (300, 'WORD_TYPE', 1, 'sostantivo', 'it');
INSERT INTO ENUM VALUES (301, 'WORD_TYPE', 2, 'verbo', 'it');
INSERT INTO ENUM VALUES (302, 'WORD_TYPE', 3, 'aggettivo', 'it');

INSERT INTO ENUM VALUES (400, 'WORD_TYPE', 1, 'noun', 'en');
INSERT INTO ENUM VALUES (401, 'WORD_TYPE', 2, 'verb', 'en');
INSERT INTO ENUM VALUES (402, 'WORD_TYPE', 3, 'adjective', 'en');
*/


CREATE TABLE `WORD` (
  `ID` INT(10) NOT NULL AUTO_INCREMENT,
  `WORD` VARCHAR(50) NOT NULL,
  `LANGUAGE_CODE` VARCHAR(50) NOT NULL,
  `TYPE` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`ID`)
);

CREATE UNIQUE INDEX WORD_IDX1 ON WORD (WORD, LANGUAGE_CODE, `TYPE`);



CREATE TABLE `WORD_DEFINITION` (
  `ID` INT(15) NOT NULL AUTO_INCREMENT,
  `WORD_ID` INT(15) NOT NULL,
  `DEFINITION` VARCHAR(1024) NOT NULL,
  `DEFINITION_SHORT` VARCHAR(50) NOT NULL,
  `SOURCE_NAME` VARCHAR(100) NOT NULL,
  `SOURCE_URL` VARCHAR(256),
  `TAGS` VARCHAR(256),
  PRIMARY KEY (`ID`)
);

ALTER TABLE WORD_DEFINITION ADD FOREIGN KEY (WORD_ID) REFERENCES WORD (ID);
CREATE UNIQUE INDEX WORD_DEFINITION_IDX1 ON WORD_DEFINITION (WORD_ID, SOURCE_NAME, DEFINITION_SHORT);



CREATE TABLE `WORD_TAG` (
  `ID` INT(10) NOT NULL AUTO_INCREMENT,
  `TAG` VARCHAR(50) NOT NULL,
  `LANGUAGE_CODE` CHAR(2) NOT NULL,
  PRIMARY KEY (`ID`)
);

CREATE UNIQUE INDEX WORD_TAG_IDX1 ON WORD_TAG (TAG,LANGUAGE_CODE);



CREATE TABLE `WORD_LIST` (
  `ID` INT(10) NOT NULL AUTO_INCREMENT,
  `WORD` VARCHAR(50) NOT NULL,
  `LANGUAGE_CODE` VARCHAR(50) NOT NULL,
  `AVOID_SOURCES` VARCHAR(1024),
  PRIMARY KEY (`ID`)
);

CREATE UNIQUE INDEX WORD_LIST_IDX1 ON WORD_LIST (WORD, LANGUAGE_CODE);



CREATE TABLE `USER` (
  `ID` INT(10) NOT NULL AUTO_INCREMENT,
  `EMAIL` VARCHAR(50) NOT NULL,
  `PASSWORD` VARCHAR(50) NOT NULL,
  `VERIFIED` BOOLEAN DEFAULT FALSE,
  `NAME` VARCHAR(50),
  `BIRTHDATE` DATE,
  `SCORE` INT(6),
  PRIMARY KEY (`ID`)
);

CREATE UNIQUE INDEX USER_IDX1 ON `USER` (EMAIL);



CREATE TABLE `USER_WORD` (
  `ID` INT(10) NOT NULL AUTO_INCREMENT,
  `USER_ID` INT(10) NOT NULL,
  `WORD_ID` INT(15) NOT NULL,
  `TAG_ID` INT(10),
  PRIMARY KEY (`ID`)
);

ALTER TABLE USER_WORD ADD FOREIGN KEY (USER_ID) REFERENCES `USER` (ID);
ALTER TABLE USER_WORD ADD FOREIGN KEY (WORD_ID) REFERENCES WORD (ID);
CREATE UNIQUE INDEX USER_WORD_IDX1 ON USER_WORD (USER_ID, WORD_ID, TAG_ID);



CREATE TABLE `USER_ACTIVITY` (
  `ID` INT(10) NOT NULL AUTO_INCREMENT,
  `CREATED` TIMESTAMP,
  `TYPE` VARCHAR(50),
  `WORD_ID` INT(15),
  `DEFINITION_ID` INT(15),
  `EMAIL` VARCHAR(50),
  `FEEDBACK` VARCHAR(1024),
  `HTTP_USER_AGENT` VARCHAR(128),
  `REMOTE_ADDR` VARCHAR(50),
  PRIMARY KEY (`ID`)
);
