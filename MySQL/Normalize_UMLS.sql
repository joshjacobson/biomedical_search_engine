# Database for normalizing the Metathesaurus
CREATE DATABASE IF NOT EXISTS sandbox;

# Concept Entity
CREATE TABLE sandbox.concept AS
SELECT DISTINCT C.CUI,C.STR
FROM umls.MRCONSO C
WHERE C.ISPREF='Y' AND C.TS='P' AND C.STT='PF';
ALTER TABLE sandbox.cuis ADD PRIMARY KEY(CUI);

# Semantic Entity
CREATE TABLE sandbox.semantic_type
SELECT s.RT,s.UI TUI,s.STY,s.STN,s.DEF,s.UN,s.NH,s.ABR,RIN FROM SRDEF s;
ALTER TABLE sandbox.semantic_type ADD PRIMARY KEY(TUI);
ALTER TABLE sandbox.semantic_type ADD INDEX `idx_semantic_STY`(STY);
SELECT * FROM sandbox.semantic_type;

# Semantic Relationship Entity
CREATE TABLE sandbox.semantic_relationship AS
SELECT s.TUI TUI1,s2.TUI RUI,s1.TUI TUI2,sr.LS
FROM umls.SRSTR sr
INNER JOIN sandbox.semantic_type s
ON sr.STY1=s.STY
INNER JOIN sandbox.semantic_type s1
ON sr.STY2=s1.STY
INNER JOIN sandbox.semantic_type s2
ON sr.RL=s2.STY;
ALTER TABLE sandbox.semantic_relationship ADD PRIMARY KEY(TUI1,RUI,TUI2),
  ADD INDEX `idx_sr_tui1`(TUI1),
  ADD INDEX `idx_sr_tui2`(TUI2),
  ADD INDEX `idx_sr_rui`(RUI);


# Concept and Semantic Relationship Entity
CREATE TABLE sandbox.concept_semantic AS
SELECT DISTINCT c.CUI,s.TUI
FROM sandbox.concept c
INNER JOIN umls.MRSTY s
ON c.CUI=s.CUI;
ALTER TABLE sandbox.concept_semantic ADD PRIMARY KEY(CUI,TUI),
  ADD INDEX `idx_cs_cui`(CUI),ADD INDEX `idx_cs_tui`(TUI);

# Concept and Normalized Words Relationships Entity.
CREATE TABLE sandbox.concept_normalize_word
( CUI CHAR(8),
  NWD VARCHAR(100));
INSERT INTO sandbox.concept_normalize_word
SELECT DISTINCT wn.CUI,wn.NWD
  FROM umls.MRXNW_ENG wn;
ALTER TABLE sandbox.concept_normalize_word ADD INDEX `idx_cn_cui`(CUI),
  ADD INDEX `idx_cn_nwd`(NWD);

# Semantic, Concept, Normalized String Entity
CREATE TABLE sandbox.semantic_concept_string AS
    SELECT
      s.TUI,
      cs.CUI,
      cnw.NWD
    FROM sandbox.semantic_type s
      INNER JOIN sandbox.concept_semantic cs
        ON s.TUI = cs.TUI
      INNER JOIN sandbox.concept_normalize_word cnw
        ON cs.CUI = cnw.CUI
    WHERE s.TUI IN ('T033', 'T034', 'T047', 'T059', 'T060', 'T061', 'T074', 'T121', 'T122', 'T123',
                            'T130', 'T184', 'T195', 'T022', 'T023', 'T200')
    UNION
    SELECT
      s.TUI,
      cs.CUI,
      cns.NSTR
    FROM sandbox.semantic_type s
      INNER JOIN sandbox.concept_semantic cs
        ON s.TUI = cs.TUI
      INNER JOIN sandbox.concept_normalize_string cns
        ON cs.CUI = cns.CUI
    WHERE s.TUI IN ('T033', 'T034', 'T047', 'T059', 'T060', 'T061', 'T074', 'T121', 'T122', 'T123',
                            'T130', 'T184', 'T195', 'T022', 'T023', 'T200');

alter table umls.MRXNS_ENG add INDEX `idx_xns_cui`(CUI);

# Concept Normalized String Entity
CREATE TABLE sandbox.concept_normalize_string AS
  SELECT DISTINCT CUI,NSTR
  FROM umls.MRXNS_ENG;
ALTER TABLE sandbox.concept_normalize_string ADD INDEX `idx_cns_cui` (CUI);

# Semantic, Concept, Normalized String Entity
CREATE TABLE sandbox.semantic_concept_nstring AS
SELECT distinct cs.TUI,cns.CUI,cns.NSTR
FROM sandbox.concept_normalize_string cns
INNER JOIN sandbox.concept_semantic cs
ON cns.CUI=cs.CUI
WHERE cs.TUI IN('T033', 'T034', 'T047', 'T059', 'T060', 'T061', 'T074', 'T121', 'T122', 'T123',
                            'T130', 'T184', 'T195', 'T022', 'T023', 'T200');
ALTER TABLE sandbox.semantic_concept_nstring ADD INDEX `idx_scns_tui`(TUI),
  ADD INDEX `idx_scns_cui`(CUI);
CREATE INDEX idx_scns_nstr ON sandbox.semantic_concept_nstring(NSTR(50));

# Concept Definition Entity
CREATE TABLE sandbox.concept_definition(
  CUI VARCHAR(8) PRIMARY KEY,
  DEF TEXT
);
INSERT IGNORE INTO sandbox.concept_definition
SELECT distinct d.CUI,d.DEF
FROM umls.MRDEF d;

ALTER TABLE sandbox.concept ADD DEF TEXT AFTER STR;
UPDATE sandbox.concept
INNER JOIN sandbox.concept_definition
ON sandbox.concept.CUI=sandbox.concept_definition.CUI
SET sandbox.concept.DEF=sandbox.concept_definition.DEF;

# Concept Relationship Entity
CREATE TABLE sandbox.concept_relationship AS
SELECT r.CUI2,r.CUI1,r.REL,r.RELA
FROM sandbox.concept c
INNER JOIN umls.MRREL r
ON c.CUI=r.CUI2
WHERE r.STYPE1='CUI';

# Subset the concepts
CREATE TABLE sandbox.concept_subset AS
SELECT DISTINCT c.CUI,c.STR,c.DEF
FROM sandbox.concept c
INNER JOIN sandbox.concept_semantic cs
  ON c.CUI=cs.CUI
WHERE cs.TUI IN('T033', 'T034', 'T047', 'T059', 'T060', 'T061', 'T074', 'T121', 'T122', 'T123',
                            'T130', 'T184', 'T195', 'T022', 'T023', 'T200');
ALTER TABLE sandbox.concept_subset ADD PRIMARY KEY(CUI);

# Concept relationship subset
CREATE TABLE sandbox.concept_relationship_subset (
  `CUI1` char(8) CHARACTER SET utf8 NOT NULL,
  `CUI2` char(8) CHARACTER SET utf8 NOT NULL,
  `REL` varchar(4) CHARACTER SET utf8 NOT NULL,
  `RELA` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY(CUI2,CUI1)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO sandbox.concept_relationship_subset
    SELECT cr.CUI1,cr.CUI2,cr.REL,cr.RELA
    FROM sandbox.concept_relationship cr
    INNER JOIN sandbox.concept_subset cs
    ON cr.CUI1=cs.CUI
    INNER JOIN sandbox.concept_subset cs1
    ON cr.CUI2=cs1.CUI;

# Get the data to load to System G
SELECT 'concept' type,s.CUI,s.STR,s.DEF
FROM sandbox.concept_subset s
UNION
SELECT 'semantic' type ,st.TUI,st.STY,st.DEF
FROM sandbox.semantic_type st
WHERE st.TUI IN('T033', 'T034', 'T047', 'T059', 'T060', 'T061', 'T074', 'T121', 'T122', 'T123',
                            'T130', 'T184', 'T195', 'T022', 'T023', 'T200')
AND RT='STY'
INTO OUTFILE 'concepts.txt'
FIELDS TERMINATED BY '|' LINES TERMINATED BY '\n';

SELECT 'concept' type,cr.CUI1,cr.CUI2,
  cr.REL,cr.RELA FROM sandbox.concept_relationship_subset cr
UNION
SELECT DISTINCT 'semantic',sr.TUI1,sr.TUI2,st.STY Rel,sr.LS FROM sandbox.semantic_relationship sr
INNER JOIN sandbox.semantic_type st
ON RUI=st.TUI
WHERE sr.TUI1 IN('T033', 'T034', 'T047', 'T059', 'T060', 'T061', 'T074', 'T121', 'T122', 'T123',
                            'T130', 'T184', 'T195', 'T022', 'T023', 'T200')
AND sr.TUI2 IN('T033', 'T034', 'T047', 'T059', 'T060', 'T061', 'T074', 'T121', 'T122', 'T123',
                            'T130', 'T184', 'T195', 'T022', 'T023', 'T200')
INTO OUTFILE 'relationships.txt'
FIELDS TERMINATED BY '|' LINES TERMINATED BY '\n';

# Add Better definitions to concepts
DROP TABLE sandbox.concept_definition;
CREATE TABLE sandbox.concept_definition
(CUI CHAR(8),
DEF TEXT,
PRIMARY KEY(CUI));
INSERT IGNORE INTO sandbox.concept_definition
    SELECT CUI,DEF FROM MRDEF WHERE SAB LIKE 'Medline%';
UPDATE sandbox.concept c
  INNER JOIN sandbox.concept_definition cd
    ON c.CUI=cd.CUI
  SET c.DEF=cd.DEF;