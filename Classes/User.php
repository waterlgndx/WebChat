<?php

require_once 'DebugHelper.php';
require_once '../config/config.php';

class User {
    private $conn;
    private $table_name = 'User';
    private $attributes;
    private $debugH;
    private $userID; //primary key, no reason to change this.
    private $dirty;
    private $passVer;

    public $email;
    public $nickname;
    public $firstName;
    public $lastName;
    public $password;
    public $userLevelID;
    public $creationDate;


    /**
     * function: interpretItem
     * purpose: converts extracted data from db to an array.
     */
    private function interpretItem($dbRow) {
        $dbUser = array(
            "UserID" => $dbRow["UserID"],
            "Email" => $dbRow["Email"],
            "Nickname" => $dbRow["Nickname"],
            "FirstName" => $dbRow["FirstName"],
            "LastName" => $dbRow["LastName"],
            "CreationDate" => $dbRow["CreationDate"],
            "UserLevelID" => $dbRow["UserLevelID"]
        );
        return $dbUser;
    }

    public function __construct($conn, $attributes) {
        $this->attributes = $attributes;
        $this->conn = $conn;
        $this->debugH = new DebugHelper();
        $this->debugH->addObject($this);
        $this->dirty = false;
    }

    public function createGuest($nickname, $userID=NULL) {
        $this->nickname = $nickname;
        $query = " INSERT INTO User (UserID, Nickname)"
             . = " VALUES(:userID, :nickname) ";
        $stmt->bindValue(":userID", $this->userID, PDO::PARAM_INT);  //this should be NULL
        $stmt->bindValue(":nickname", $this->nickname, PDO::PARAM_STR);

        
    }

    public function createNew($email, $nickname, $lastName, $firstName=NULL,
        $userLevelID=NULL, $userID=NULL, $creationDate=NULL,
        $uploadPath=NULL ) {  //password is generated

        $this->email = $email;
        $this->nickname = $nickname;
        $this->lastName = $lastName;
        $this->firstName = $firstName;
        if (empty($userLevelID) || ($userLevelID < $GLOBALS['MIN_USER_LEVEL_LISTED'])) {
            $this->userLevelID = $GLOBALS['MIN_USER_LEVEL_LISTED'];
        } else {
            $this->userLevelID = $userLevelID;
        }
        $this->userID = $userID;
        $this->creationDate = $creationDate;

        $query = " INSERT INTO User (UserID, Email, Nickname, FirstName, LastName, Password, "
                ." PassVer, UserLevelID, CreationDate) "
                ." VALUES (:userID, :email, :nickname, :firstName, :lastName, :password, "
                ." NULL, :userLevelID, :creationDate ); ";

        $passGen = chr(random_int(33,126)); //generate random ascii sequence
        for ($i=1; $i<15; $i++) {
            $passGen .= chr(random_int(33,126));
        }

        //send email with confirmation link
		$headers = "From: " . $GLOBALS['AUTO_ADMIN_NAME'] . " " . $GLOBALS['AUTO_ADMIN_EMAIL'];
		$subject = "Confirm your email address";
		$message = "Please confirm your email address at ". $GLOBALS['FQP'] . "/verifyemail.html?confirmNum=$passGen&Email=$email \n"
		         . "If you have problems you may go back to ". $GLOBALS['FQP'] . "/getconfirm.html and try again!";
		mail($email,$subject,$message,$headers);
        $passGen = crypt($passGen);

        $stmt = $this->conn->prepare($query, $this->attributes);
        $stmt->bindValue(":userID", $this->userID, PDO::PARAM_INT);  //this should be NULL
        $stmt->bindValue(":email", $this->email, PDO::PARAM_STR);
        $stmt->bindValue(":nickname", $this->nickname, PDO::PARAM_STR);
        $stmt->bindValue(":firstName", $this->firstName, PDO::PARAM_STR);
        $stmt->bindValue(":lastName", $this->lastName, PDO::PARAM_STR);
        $stmt->bindValue(":password", $passGen, PDO::PARAM_STR);
        $stmt->bindValue(":userLevelID", $this->userLevelID, PDO::PARAM_INT);
        $stmt->bindValue(":creationDate", $this->creationDate, PDO::PARAM_INT);
        $stmt->execute() or $this->debugH->errormail("Unknown", "Create new user failed", "Create User Query failed.");
        if ($stmt->rowCount()==0)
            return;
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return print_r(json_encode($this->interpretItem($row)),true);
    }

    public function setEmail($email) {
        if ($this->userID) {
            if ($this->email != $email) {
                $this->email = $email;
                $this->dirty = true;
            }
        } else {
            throw new Exception('Set email for an uninitialized ' . get_class($this));
        }
    }

    public function setDisplayName($displayName) {
        if ($this->userID) {
            if ($this->displayName != $displayName) {
                $this->displayName = $displayName;
                $this->dirty = true;
            }
        } else {
            throw new Exception ('Set displayName for an uninitialized ' . get_class($this));
        }
    }

    public function setFirstName($firstName) {
        if ($this->userID) {
            if ($this->firstName != $firstName) {
                $this->firstName = $firstName;
                $this->dirty = true;
            }
        } else {
            throw new Exception ('Set firstName for an uninitialized ' . get_class($this));
        }
    }

    public function setLastName($lastName) {
        if ($this->userID) {
            if ($this->lastName != $lastName) {
                $this->lastName = $lastName;
                $this->dirty = true;
            }
        } else {
            throw new Exception ('Set lastName for an uninitialized ' . get_class($this));
        }
    }

    public function setPassword($password) {
        if ($this->userID) {
            if ($this->password != $password) {
                $this->password = md5($password);
                $this->dirty = true;
            }
        } else {
            throw new Exception ('Set password for an uninitialized ' . get_class($this));
        }
    }

    public function setUserLevelID($userLevelID) {
        if ($this->userID) {
            if ($this->userLevelID != $userLevelID) {
                $this->userLevelID = $userLevelID;
                $this->dirty = true;
            }
        } else {
            throw new Exception ('Set userLevelID for an uninitialized ' . get_class($this));
        }
    }

    public function setCreationDate($creationDate) {
        if ($this->userID) {
            if ($this->creationDate != $creationDate) {
                $this->creationDate = $creationDate;
                $this->dirty = true;
            }
        } else {
            throw new Exception ('Set creationDate for an uninitialized ' . get_class($this));
        }
    }
    
    public function getByID($id, $json=false) {
        $query = "SELECT UserID, Email, DisplayName, FirstName, LastName, Password, UserLevelID, CreationDate "
               . "FROM User "
               . "Where User.UserID = :id ";

        $stmt = $this->conn->prepare($query, $this->attributes);
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute() or $this->debugH->errormail("Unknown", "Get by ID failed", "User Query failed.");
        if ($stmt->rowCount()==0)
            return;
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($json) {
            return print_r(json_encode($this->interpretItem($row)), true);
        } else {
            $this->userID = $row["UserID"];
            $this->email = $row["Email"];
            $this->displayName = $row["DisplayName"];
            $this->firstName = $row["FirstName"];
            $this->lastName = $row["LastName"];
            $this->creationDate = $row["CreationDate"];
            $this->password = $row["Password"];
            $this->userLevelID = $row["UserLevelID"];
        }
    }

    public function get($userEmail, $json=true) {
        $query = "SELECT UserID, Email, DisplayName, FirstName, LastName, Password, UserLevelID, CreationDate "
               . "FROM User "
               . "Where User.Email = :email ";

        $stmt = $this->conn->prepare($query, $this->attributes);
        $stmt->execute(array(":email"=>$userEmail));
        if ($stmt->rowCount()==0) {
            if ($json)
                return json_encode(array("message" => "No users found."));
            else
                return;
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($json) {
            return print_r(json_encode($this->interpretItem($row)), true);
        } else {
            $this->userID = $row["UserID"];
            $this->email = $row["Email"];
            $this->displayName = $row["DisplayName"];
            $this->firstName = $row["FirstName"];
            $this->lastName = $row["LastName"];
            $this->password = $row["Password"];
            $this->userLevelID = $row["UserLevelID"];
        }
    }

    public function getAllJson() {
        $query = "SELECT Email, DisplayName, FirstName, LastName, Password, UserLevelID, CreationDate "
               . "FROM User ";

        $stmt = $this->conn->prepare($query, $this->attributes);
        $stmt->execute();
        if ($stmt->rowCount()==0)
            return json_encode(array("message" => "No users found."));
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            if (empty($userArray)){
                $userArray=array($this->interpretItem($row));
            } else {
                array_push($userArray, $this->interpretItem($row));
            }
        }
        return print_r(json_encode($userArray), true);
    }

    public function updateDB() {
        if (isset($this->userID) && $this->dirty) {
            if (!empty($this->password)) //we don't want the password to be blank!
                $passQuery = " Password = :password ,";
            else
                $passQuery = "";
            $query = " Update `User` set Email = :email, DisplayName = :displayName, "
                   . " FirstName = :firstName, LastName = :lastName, $passQuery "
                   . " UserLevelID = :userLevelID, CreationDate = :creationDate, "
                   . " UploadPath = :uploadPath "
                   . " WHERE `User`.UserID = :userID ;";

            $stmt = $this->conn->prepare($query, $this->attributes);
            $stmt->bindValue(":userID", $this->userID, PDO::PARAM_INT);  //this should be NULL
            $stmt->bindValue(":email", $this->email, PDO::PARAM_STR);
            $stmt->bindValue(":displayName", $this->displayName, PDO::PARAM_STR);
            $stmt->bindValue(":firstName", $this->firstName, PDO::PARAM_STR);
            $stmt->bindValue(":lastName", $this->lastName, PDO::PARAM_STR);
            if (!empty($this->password))
                $stmt->bindValue(":password", $this->password, PDO::PARAM_STR);
            $stmt->bindValue(":userLevelID", $this->userLevelID, PDO::PARAM_INT);
            $stmt->bindValue(":creationDate", $this->creationDate, PDO::PARAM_STR);
            $stmt->bindValue(":uploadPath", $this->uploadPath, PDO::PARAM_STR);
            $stmt->execute() or $this->debugH->errormail("Unknown", "Update user failed", "Update User Query failed.");
            if ($stmt->rowCount() == 0)
                return json_encode(array("message"=>"already up to date!"));
            else {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return print_r(json_encode($this->interpretItem($row)),true);
            }
        } else {
            return json_encode(array("message"=>"no changes found to update!"));
        }
    }
}

 ?>
