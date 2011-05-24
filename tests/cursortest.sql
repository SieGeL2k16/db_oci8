/*
 * This code is used as testbed for OCI8 class to allow passing cursor variables from/to pl/sql
 * Unfortunatly we have to create a package for this, PL/SQL does not like to use a REF CURSOR.
 * First we create the package, next we call an anonymous procedure which uses the package
 * and fetches data from this cursor.
 */

/* Package spec declares cursor type */

CREATE OR REPLACE PACKAGE CURSORTEST
AS
	TYPE	genericCV IS REF CURSOR;
	PROCEDURE GetCursor(mycv IN OUT genericCV);
	PROCEDURE CloseCursor(mycv IN genericCV);

END CURSORTEST;
/

/* Package body declares the corresponding code */

CREATE OR REPLACE PACKAGE BODY CURSORTEST
AS
	/* Opens the cursor as SELECT * FROM emp; */

	PROCEDURE GetCursor(mycv IN OUT genericCV)
	IS
	BEGIN
		OPEN mycv FOR SELECT * FROM emp;
	END GetCursor;

	/* Cursor variables MUST (!) be closed, else we run into trouble: */

	PROCEDURE CloseCursor(mycv IN genericCV)
	IS
	BEGIN
		IF mycv%ISOPEN THEN
			CLOSE mycv;
		END IF;
	END CloseCursor;

END CURSORTEST;
/

/*
 * Anonymous procedure to read the data from cursor variable which is opened inside the package.
 */

DECLARE
  TYPE genericCV IS REF CURSOR;
	mycv genericCV;
	mydata emp%ROWTYPE;

BEGIN
	CURSORTEST.GetCursor(mycv);
	LOOP
		FETCH mycv INTO mydata;
		EXIT WHEN mycv%NOTFOUND;
		DBMS_OUTPUT.PUT_LINE('EMPNO='||mydata.EMPNO);
	END LOOP;
	CURSORTEST.CloseCursor(mycv);
END;
/
