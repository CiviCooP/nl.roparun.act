<?php
/**
 * @author Jaap Jansma <jaap.jansma@civicoop.org>
 * @license AGPL-3.0
 */

function civicrm_api3_act_Get($params) {
  $teams = array();
  $startLocations = CRM_Core_OptionGroup::values('start_locations');
  $config = CRM_Generic_Config::singleton();
  $websiteConfig = CRM_Generic_WebsiteTypeConfig::singleton();
  $event_id = CRM_Generic_CurrentEvent::getCurrentRoparunEventId();
  $countries = CRM_Core_PseudoConstant::country();
  $teamSql = "SELECT civicrm_participant.id, 
             civicrm_contact.id as team_id,
             civicrm_contact.display_name,
             `{$config->getTeamDataCustomGroupTableName()}`.`{$config->getTeamNrCustomFieldColumnName()}` AS `team_nr`,
             `{$config->getTeamDataCustomGroupTableName()}`.`{$config->getTeamNameCustomFieldColumnName()}` AS `team_name`,
             `{$config->getTeamDataCustomGroupTableName()}`.`{$config->getStartLocationCustomFieldColumnName()}` AS `start_location`,
             `{$config->getTeamDataCustomGroupTableName()}`.`{$config->getAverageSpeedCustomFieldColumnName()}` AS `average_speed`,
             civicrm_address.city as city,
             civicrm_address.country_id as country_id,
             website.url as website,
             facebook.url as facebook,
             instagram.url as instagram,
             twitter.url as twitter,
             phone.phone as phone_during_event       
             FROM civicrm_contact 
             INNER JOIN civicrm_participant ON civicrm_participant.contact_id = civicrm_contact.id 
             INNER JOIN civicrm_participant_status_type ON civicrm_participant.status_id = civicrm_participant_status_type.id
             LEFT JOIN civicrm_address ON civicrm_address.contact_id = civicrm_contact.id AND civicrm_address.location_type_id = %1
             LEFT JOIN civicrm_address billing_address ON billing_address.contact_id = civicrm_contact.id AND billing_address.location_type_id = %2
             LEFT JOIN `{$config->getTeamDataCustomGroupTableName()}` ON `{$config->getTeamDataCustomGroupTableName()}`.entity_id = civicrm_participant.id
             LEFT JOIN civicrm_website website ON website.contact_id = civicrm_contact.id and website.website_type_id = {$websiteConfig->getWebsiteWebsiteTypeId()}
             LEFT JOIN civicrm_website facebook ON facebook.contact_id = civicrm_contact.id and facebook.website_type_id = {$websiteConfig->getFacebookWebsiteTypeId()}
             LEFT JOIN civicrm_website instagram ON instagram.contact_id = civicrm_contact.id and instagram.website_type_id = {$websiteConfig->getInstagramWebsiteTypeId()}
             LEFT JOIN civicrm_website twitter ON twitter.contact_id = civicrm_contact.id and twitter.website_type_id = {$websiteConfig->getTwitterWebsiteTypeId()}
             LEFT JOIN civicrm_phone phone ON phone.contact_id = civicrm_contact.id AND phone.phone_type_id = {$config->getDuringEventPhoneTypeId()}
             WHERE civicrm_participant.status_id IN (".implode(',', $config->getActiveParticipantStatusIds()).") 
             AND civicrm_participant.event_id = %3 AND civicrm_participant.role_id = %4 
             ORDER BY team_nr, team_name";
  $teamParams[1] = array($config->getVestingsplaatsLocationTypeId(), 'Integer');
  $teamParams[2] = array($config->getBillingLocationTypeId(), 'Integer');
  $teamParams[3] = array($event_id, 'Integer');
  $teamParams[4] = array($config->getTeamParticipantRoleId(), 'Integer');
  $teamDao = CRM_Core_DAO::executeQuery($teamSql, $teamParams);
  while($teamDao->fetch()) {
    $country = '';
    if ($teamDao->country_id) {
      $country = $countries[$teamDao->country_id];
    }
    $team = array();
    $team['id'] = $teamDao->id;
    $team['name'] = $teamDao->team_name;
    $team['teamnr'] = $teamDao->team_nr;
    $team['start_location'] = isset($startLocations[$teamDao->start_location]) ? $startLocations[$teamDao->start_location] : '';
    $team['average_speed'] = 0.00;
    if ($teamDao->average_speed ) {
      $team['average_speed'] = (float) $teamDao->average_speed;
    }
    $team['city'] = $teamDao->city;
    $team['country'] = $country;
    $team['website'] = $teamDao->website;
    $team['facebook'] = $teamDao->facebook;
    $team['instagram'] = $teamDao->instagram;
    $team['twitter'] = $teamDao->twitter;
    $team['phone_during_event'] = $teamDao->phone_during_event;

    $team['team_members'] = _civicrm_api3_act_get_team_members($teamDao->team_id, $event_id);


    $teams[$teamDao->id] = $team;
  }

  return civicrm_api3_create_success($teams, $params, 'Act', 'Get');
}

function _civicrm_api3_act_get_team_members($team_id, $event_id) {
  $teamMembers = array();
  $config = CRM_Generic_Config::singleton();
  $countries = CRM_Core_PseudoConstant::country();
  $teamMemberSql = "
    SELECT DISTINCT
    civicrm_contact.id,
    civicrm_contact.display_name, 
    civicrm_contact.first_name,
    civicrm_contact.middle_name,
    civicrm_contact.last_name,
    civicrm_address.street_address,
    civicrm_address.postal_code, 
    civicrm_address.city,
    civicrm_address.country_id,
    civicrm_phone.phone,
    civicrm_email.email,
    team_member_data.{$config->getTeamRoleCustomFieldColumnName()} as role,
    ice.{$config->getICEWaarschuwInGevalVanNoodCustomFieldColumnName()} as waarschuw_in_nood,
    ice.{$config->getICETelefoonInGevalVanNoodCustomFieldColumnName()} as telefoon_in_nood,
    civicrm_participant.status_id as status_id,
    (CASE
      WHEN civicrm_relationship.id IS NOT NULL THEN 1
      ELSE 0 
    END) AS is_team_captain
    FROM civicrm_contact
    INNER JOIN civicrm_participant ON civicrm_contact.id = civicrm_participant.contact_id
    INNER JOIN {$config->getTeamMemberDataCustomGroupTableName()} team_member_data ON team_member_data.entity_id = civicrm_participant.id
    LEFT JOIN civicrm_address ON civicrm_address.contact_id = civicrm_contact.id AND civicrm_address.is_primary = 1
    LEFT JOIN civicrm_phone ON civicrm_phone.contact_id = civicrm_contact.id AND civicrm_phone.is_primary = 1
    LEFT JOIN civicrm_email ON civicrm_email.contact_id = civicrm_contact.id AND civicrm_email.is_primary = 1
    LEFT JOIN {$config->getICECustomGroupTableName()} `ice` ON `ice`.entity_id = civicrm_contact.id
    LEFT JOIN civicrm_relationship ON civicrm_relationship.contact_id_a = civicrm_contact.id 
      AND civicrm_relationship.relationship_type_id = %1
      AND civicrm_relationship.is_active = 1 
      AND (civicrm_relationship.start_date IS NULL OR civicrm_relationship.start_date <= CURRENT_DATE()) 
      AND (civicrm_relationship.end_date IS NULL OR civicrm_relationship.end_date >= CURRENT_DATE())
      AND civicrm_relationship.contact_id_b = team_member_data.{$config->getMemberOfTeamCustomFieldColumnName()}
    WHERE civicrm_contact.is_deleted = '0'
    AND team_member_data.{$config->getMemberOfTeamCustomFieldColumnName()} = %2
    AND civicrm_participant.event_id = %3
    AND civicrm_participant.status_id IN (".implode(", ", $config->getActiveParticipantStatusIds()).")
    ORDER BY display_name ASC
    
  ";
  $sqlParams[1] = array($config->getTeamCaptainRelationshipTypeId(), 'Integer');
  $sqlParams[2] = array($team_id, 'Integer');
  $sqlParams[3] = array($event_id, 'Integer');

  $teamMembersDao = CRM_Core_DAO::executeQuery($teamMemberSql, $sqlParams);
  while ($teamMembersDao->fetch()) {
    $teamMember = array();
    $teamMember['id'] = $teamMembersDao->id;
    $teamMember['is_team_captain'] = $teamMembersDao->is_team_captain;
    $teamMember['display_name'] = $teamMembersDao->display_name;
    $teamMember['phone'] = $teamMembersDao->phone;
    $teamMember['email'] = $teamMembersDao->email;

    $teamMember['address'] = $teamMembersDao->street_address;
    $teamMember['postal_code'] = $teamMembersDao->postal_code;
    $teamMember['city'] = $teamMembersDao->city;

    $country = '';
    if ($teamMembersDao->country_id) {
      $country = $countries[$teamMembersDao->country_id];
    }
    $teamMember['country'] = $country;

    $teamMember['role'] = $teamMembersDao->role;
    $teamMember['waarschuw_in_geval_van_nood'] = $teamMembersDao->waarschuw_in_nood;
    $teamMember['telefoon_in_geval_van_nood'] = $teamMembersDao->telefoon_in_nood;
    $teamMembers[] = $teamMember;
  }
  return $teamMembers;
}