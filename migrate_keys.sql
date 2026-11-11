ALTER TABLE provision_blf_keys MODIFY key_type
  ENUM('blf','speed_dial','line','dtmf','prefix','local_group','xml_group','xml_browser','ldap','conference','forward','transfer','hold','dnd','recall','reboot','hot_desking','acd','zero_touch','url','multicast','group_listening','paging_list','voicemail','pickup','park','intercom','redial','record') DEFAULT 'blf';
