#!/usr/bin/perl

########################################################################
# Pandora FMS - Remote Event Tool (via WEB API) 
########################################################################
# Copyright (c) 2013 Artica Soluciones Tecnologicas S.L
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License version 2
########################################################################

# Includes list
use strict;
use LWP::Simple;
use URI::Escape;

# Init
tool_api_init();

# Main
tool_api_main();

########################################################################
# Print a help screen and exit.
########################################################################
sub help_screen () {
	
	print "
Options to create event: 

\t$0 -p <path_to_consoleAPI> -create event <options> 

Where options:\n
	-u <credentials>
	-create_event 
	-name <event_name>          : Free text
	-group <id_group>           : Group ID (use 0 for 'all') 
	-type <event_type>          : unknown, alert_fired, alert_recovered, alert_ceased
	                              alert_manual_validation, system, error, new_agent
	                              configuration_change, going_unknown, going_down_critical,
	                              going_down_warning, going_up_normal
	
Optional parameters:
	
	[-agent <id_agent>]         : Agent ID
	[-user <id_user>]           : User comment (use in combination with -comment option)
	[-status <status>]          : 0 New, 1 Validated, 2 In process
	[-am <id_agent_module>]     : ID Agent Module linked to event
	[-alert <id_alert_am>]      : ID Alert Module linked to event
	[-criticity <criticity>]    : 0 Maintance, 1 Informative, 2 Normal, 
	                              3 Warning, 4 Crit, 5 Minor, 6 Major 
	
	[-comment <user_comment>]   : Free text for comment
	[-tag <tags>]               : Tag (must exist in the system to be imported)
	[-source <source>]          : (By default 'Pandora')
	[-c_instructions <critical_instructions>]
	[-w_instructions <warning_instructions>]
	[-u_instructions <unknown_instructions>]
	[-owner <owner_user>]       : Use the login name, not the descriptive
	
	
Credential/API syntax:
	
	<credentials>               : API credentials separated by comma: <api_pass>,<user>,<pass>

Example of event generation:
	
	$0 -p http://localhost/pandora_console/include/api.php -u 1234,admin,pandora
	   -create_event -name \"SampleEvent\" -group 2 -type \"system\" -agent 189 -status 0 -user \"admin\"
	   -criticity 3 -am 0 -alert 9 -c_instructions \"Critical instructions\" -w_instructions \"Warning instructions\"
	   -u_instructions \"Unknown instructions\" -source \"Commandline\" -tag \"Tags\" -owner \"other\"
	
	
";
	
	exit;
}

########################################################################
# Init screen
########################################################################
sub tool_api_init () {
	
	print "\nPandora FMS Remote Event Tool Copyright (c) 2013 Artica ST\n";
	print "This program is Free Software, licensed under the terms of GPL License v2\n";
	print "You can download latest versions and documentation at http://www.pandorafms.org\n\n";
	
	if ($#ARGV < 0) {
		help_screen();
	}
	
	if (($ARGV[0] eq '-h') || ($ARGV[0] eq '-help')) {
		help_screen();
	}
	
}




########################################################################
########################################################################
# MAIN
########################################################################
########################################################################

sub tool_api_main () {
	
	my $api_path;
	my $event_name;
	my $id_group;
	my $event_type;
	my $data_event;
	my $credentials;
	my $api_pass;
	my $db_user;
	my $db_pass;
	my @db_info;
	my $id_agent;
	my $agent_name;
	my $id_user = '';
	my $status = '';
	my $id_agent_module = '';
	my $module_name = '';
	my $id_alert_am = '';
	my $criticity = '';
	my $user_comment = '';
	my $tags = '';
	my $source = '';
	my $id_extra = '';
	my $critical_instructions = '';
	my $warning_instructions = '';
	my $unknown_instructions = '';
	my $owner_user = '';
	my $id_event;
	my $option = $ARGV[4];
	my $call_api;
	
	#~ help or api path (required)
	if ($ARGV[0] eq '-h') {
		print "HELP!\n";
		help_screen();
	}
	elsif ($ARGV[0] ne '-p') {
		print "[ERROR] Missing API path! Read help info:\n\n";
		help_screen ();
	}
	else {
		$api_path = $ARGV[1];
	}
	
	#~ credentials of database
	if ($ARGV[2] eq '-u') {
		$credentials = $ARGV[3];
		@db_info = split(',', $credentials);
		
		if ($#db_info < 2) {
			print "[ERROR] Invalid database credentials! Read help info:\n\n";
			help_screen();
		}
		else {
			$api_pass = $db_info[0];
			$db_user = $db_info[1];
			$db_pass = $db_info[2];
		}
	}
	else {
		print "[ERROR] Missing database credentials! Read help info:\n\n";
		help_screen ();
	}
	
	if ($ARGV[4] eq '-create_event') {
		#~ event name (required)
		if ($ARGV[5] ne '-name') {
			print "[ERROR] Missing event name! Read help info:\n\n";
			help_screen ();
		}
		else {
			$event_name = uri_escape($ARGV[6]);
		}
		
		#~ id group (required)
		if ($ARGV[7] ne '-group') {
			print "[ERROR] Missing event group! Read help info:\n\n";
			help_screen ();
		}
		else {
			$id_group = $ARGV[8];
		}
		
		#~ id group (required)
		if ($ARGV[9] ne '-type') {
			print "[ERROR] Missing event type! Read help info:\n\n";
			help_screen ();
		}
		else {
			$event_type = $ARGV[10];
		}
		
		my $i = 0;
		foreach (@ARGV) {
			my $line = $_;
			if ($line eq '-agent') {
				$id_agent = $ARGV[$i + 1];
			}
			if ($line eq '-user') {
				$id_user = $ARGV[$i + 1];
			}
			if ($line eq '-status') {
				$status = $ARGV[$i + 1];
			}
			if ($line eq '-am') {
				$id_agent_module = $ARGV[$i + 1];
			}
			if ($line eq '-alert') {
				$id_alert_am = $ARGV[$i + 1];
			}
			if ($line eq '-criticity') {
				$criticity = $ARGV[$i + 1];
			}
			if ($line eq '-comment') {
				$user_comment = $ARGV[$i + 1];
			}
			if ($line eq '-tag') {
				$tags = $ARGV[$i+1];
			}
			if ($line eq '-source') {
				$source = uri_escape($ARGV[$i + 1]);
			}
			if ($line eq '-c_instructions') {
				$critical_instructions = uri_escape($ARGV[$i + 1]);
			}
			if ($line eq '-w_instructions') {
				$warning_instructions = uri_escape($ARGV[$i + 1]);
			}
			if ($line eq '-u_instructions') {
				$unknown_instructions = uri_escape($ARGV[$i + 1]);
			}
			if ($line eq '-owner') {
				$owner_user = $ARGV[$i+1];
			}
			$i++;
		}
		
		$data_event = $event_name . "|" .
			$id_group . "|" .
			$id_agent . "|" .
			$status . "|" .
			$id_user . "|" .
			$event_type . "|" .
			$criticity . "|" .
			$id_agent_module . "|" .
			$id_alert_am . "|" .
			$critical_instructions . "|" .
			$warning_instructions . "|" .
			$unknown_instructions . "|" .
			$user_comment . "|" .
			$owner_user . "|" .
			$source . "|" .
			$tags;
		
		$call_api = $api_path . '?' .
			'op=set&' .
			'op2=create_event&' .
			'id=' . $event_name . '&' .
			'other=' . $data_event . '&' .
			'other_mode=url_encode_separator_|&' .
			'apipass=' . $api_pass . '&' .
			'user=' . $db_user . '&' .
			'pass=' . $db_pass;
		
		#DEBUG TRACE#
		
		#print "$call_api\n";
		
	}
	
	my @args = @ARGV;
	my $ltotal = $#args; 
	
	if ($ltotal < 0) {
		print "[ERROR] No valid arguments. Read help info:\n\n";
		help_screen ();
		exit;
	}
	else {
		my $content = get($call_api);
		print("\n\n");
		print($call_api);
		print("\n\n");
		
		if ($option eq '-create_event') {
			if ($content eq undef) {
				print "[ERROR] Not respond or bad syntax. Read help info:\n\n";
				help_screen();
			}
			else {
				print "Event ID: $content";
			}
		} 
	}
	
	print "\nExiting!\n\n";
	
	exit;
}
