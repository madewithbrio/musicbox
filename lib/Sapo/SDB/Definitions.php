<?php 
class Sapo_SDB_Definitions {
	public static function getClassMap()
	{
		$classmap = array(
		  'ESBCredentials' => 'Sapo_SDB_Definitions_ESBCredentials_t',
	      'ESBRoles' => 'Sapo_SDB_Definitions_ESBRoles_t',
		);
		return $classmap;
	}
}

class Sapo_SDB_Definitions_ESBCredentials_t {

  /* Type: string MinOcurs: 0 MaxOcurs: 1 */
  public $ESBUsername;

  /* Type: string MinOcurs: 0 MaxOcurs: 1 */
  public $ESBPassword;

  /* Type: string MinOcurs: 0 MaxOcurs: 1 */
  public $ESBToken;

  /* Type: ESBRoles MinOcurs: 0 MaxOcurs: 1 */
  public $ESBRoles;

  /* Type: string MinOcurs: 0 MaxOcurs: 1 */
  public $ESBTokenTimeToLive;

  /* Type: string MinOcurs: 0 MaxOcurs: 1 */
  public $ESBTokenExtraInfo;

  /* Type: string MinOcurs: 0 MaxOcurs: 1 */
  public $ESBPrimaryId;

  /* Type: string MinOcurs: 0 MaxOcurs: 1 */
  public $ESBUserType;

  /* Type: string MinOcurs: 0 MaxOcurs: 1 */
  public $ESBCredentialsStore;
}

class Sapo_SDB_Definitions_ESBRoles_t {

  /* Type: string MinOcurs: 1 MaxOcurs: unbounded */
  public $ESBRole = array();
}
