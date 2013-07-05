CREATE TABLE IF NOT EXISTS `access_data` (
    id              BIGINT NOT NULL AUTO_INCREMENT,
    time            TIMESTAMP,
    ip              VARCHAR(15),
    site            VARCHAR(300),
    referrer        VARCHAR(300),
    
    PRIMARY KEY (id)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `access_data_parsed` (
    id              BIGINT,
    day_of_week     TINYINT,    -- 0=Sunday, 1=Monday, ...
    hour            TINYINT,
    
    -- Format: Id as used in URL: /unigeschichte/professorenkatalog/leipzig/<id>[/|.html]
    accessed_prof   VARCHAR(40) DEFAULT NULL,
    
    -- Used URLs: /unigeschichte/professorenkatalog/epoche/... & /unigeschichte/professorenkatalog/Zeitraum/...
    accessed_time_from  TINYINT DEFAULT NULL,
    accessed_time_to    TINYINT DEFAULT NULL,
    epoche_request      BOOLEAN DEFAULT FALSE,
    
    -- Format: Id as used in URL: /unigeschichte/professorenkatalog/fak/<id>[/.*]
    accessed_faculty    VARCHAR(30) DEFAULT NULL, -- Format: Is as used in URL
    
    referrer_domain VARCHAR(50),
    referrer_se     ENUM('google') DEFAULT NULL,
    country         VARCHAR(2),
    ip_institution  VARCHAR(10),
    
    PRIMARY KEY (id),
    FOREIGN KEY (id) REFERENCES access_data(id)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `access_data_se_terms` (
    id              BIGINT,
    term            VARCHAR(50),
    
    PRIMARY KEY (id, term),
    FOREIGN KEY (id) REFERENCES access_data(id)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `ipv4_country` (
    start_ip        BIGINT,
    end_ip          BIGINT,
    country2        VARCHAR(2),
    
    PRIMARY KEY (start_ip),
    UNIQUE (end_ip)
) ENGINE=MyISAM;

