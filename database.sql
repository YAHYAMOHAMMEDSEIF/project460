-- ONYO: faili hili linafuta database ya zamani ya "ruhusa" na kuunda upya.
DROP DATABASE IF EXISTS ruhusa;
CREATE DATABASE ruhusa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ruhusa;

-- Idara (zenye madivisheni) na Vitengo Maalum (vinaomba moja kwa moja kwa Mtakwimu Mkuu)
CREATE TABLE departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  jina VARCHAR(120) NOT NULL,
  aina ENUM('idara','kitengo') NOT NULL DEFAULT 'idara'
);

CREATE TABLE divisions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  department_id INT NOT NULL,
  jina VARCHAR(120) NOT NULL,
  FOREIGN KEY (department_id) REFERENCES departments(id)
);

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  jina VARCHAR(100) NOT NULL,
  role ENUM('mfanyakazi','meneja','mkurugenzi','chief','hr') NOT NULL DEFAULT 'mfanyakazi',
  department_id INT NULL,
  division_id INT NULL,
  email VARCHAR(120) NOT NULL,
  badilisha_nenosiri TINYINT(1) NOT NULL DEFAULT 0,   -- 1 = lazima abadilishe nenosiri akiingia
  FOREIGN KEY (department_id) REFERENCES departments(id),
  FOREIGN KEY (division_id) REFERENCES divisions(id)
);

CREATE TABLE requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  department_id INT NULL,
  division_id INT NULL,
  aina VARCHAR(60) NOT NULL,
  tarehe_kuanza DATE NOT NULL,
  tarehe_kumaliza DATE NOT NULL,
  maelezo TEXT,
  kiambatisho VARCHAR(255),
  jina_asili VARCHAR(255),
  njia VARCHAR(60) NOT NULL,                       -- mfano: meneja,mkurugenzi,chief
  hatua ENUM('meneja','mkurugenzi','chief','imekamilika') NOT NULL,
  hali ENUM('Inasubiri','Imekubaliwa','Imekataliwa') DEFAULT 'Inasubiri',
  chombo VARCHAR(60) NULL,                         -- safari ya kikazi: chombo cha usafiri
  madhumuni TEXT NULL,                             -- safari ya kikazi: madhumuni ya safari
  maoni TEXT,
  mwamuzi_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (mwamuzi_id) REFERENCES users(id)
);

CREATE TABLE approvals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  request_id INT NOT NULL,
  hatua VARCHAR(20) NOT NULL,
  user_id INT NOT NULL,
  uamuzi ENUM('Imekubaliwa','Imekataliwa') NOT NULL,
  maoni TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES requests(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  ujumbe TEXT NOT NULL,
  imesomwa TINYINT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- MAJINA YA MFANO: badilisha yawe majina halisi ya idara, divisheni na vitengo vya OCGS.
INSERT INTO departments (id, jina, aina) VALUES
 (1,'Idara ya Utawala na Utumishi','idara'),
 (2,'Idara ya Takwimu za Kiuchumi','idara'),
 (3,'Idara ya Takwimu za Kijamii','idara'),
 (4,'Idara ya Mipango na Utafiti','idara'),
 (5,'Kitengo cha Ukaguzi wa Ndani','kitengo'),
 (6,'Kitengo cha Sheria','kitengo'),
 (7,'Kitengo cha TEHAMA','kitengo'),
 (8,'Kitengo cha Mawasiliano','kitengo');

INSERT INTO divisions (id, department_id, jina) VALUES
 (1,1,'Divisheni ya Utumishi'),
 (2,1,'Divisheni ya Fedha na Uhasibu'),
 (3,2,'Divisheni ya Hesabu za Taifa'),
 (4,2,'Divisheni ya Takwimu za Biashara'),
 (5,3,'Divisheni ya Sensa na Makazi'),
 (6,3,'Divisheni ya Takwimu za Afya na Elimu'),
 (7,4,'Divisheni ya Mipango'),
 (8,4,'Divisheni ya Utafiti');
