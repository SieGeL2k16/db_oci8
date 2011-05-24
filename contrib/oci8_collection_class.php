<?php
/**
 * Collection class allows to bind PHP arrays to Oracle and vice-versa.
 * This class extends the OCI8 class with additional specialized methods.
 * @package db_oci8
 * @author Sascha 'SieGeL' Pfalz <php@saschapfalz.de>
 * @version 0.1 (26-Jul-2009)
 * $Id: oci8_collection_class.php,v 1.1 2010/08/07 13:00:48 siegel Exp $
 * @license http://opensource.org/licenses/bsd-license.php BSD License
 * @filesource
 */

class db_oci8_collection_class extends db_oci8
  {

  /**
   * Creates new collection object and returns the result.
   * @param string $p_type Name of type to use.
   * @param string $p_schema Optional name of schema to use, defaults to current schema.
   * @return mixed Either the Collection object or NULL in case of an error. Use getSQLError() to find out the error.
   */
  function NewCollection($p_type, $p_schema = '')
    {
    $obj = @OCI_New_Collection($this->sock, $p_type, $p_schema);
    if(is_object($obj) == false)
      {
      $dummy = oci_error($this->sock);
      $this->sqlerr     = $dummy['code'];
      $this->sqlerrmsg  = $dummy['message'];
      return(NULL);
      }
    return($obj);
    }

  /**
   * Frees the collection resource.
   * @param object $p_collection The return value of NewCollection().
   * @return boolean Result of the free() call, FALSE if given parameter was not an object at all.
   */
  function FreeCollection($p_collection)
    {
    if(is_object($p_collection))
      {
      return($p_collection->free());
      }
    return(false);
    }

  /**
   * Checks if a given type already exists in the database.
   * @param string $p_type Name of type to check.
   * @param string $p_schema Optional name of schema to use, defaults to current schema.
   * @return boolean TRUE if type exists, else FALSE.
   */
  function CheckType($p_type, $p_schema = '')
    {

    }

  } // EOF
?>
