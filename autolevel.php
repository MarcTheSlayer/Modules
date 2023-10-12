<?php
/**
	Modified by MarcTheSlayer
	05/04/2012 - v1.0.0
	+ This is a modified version of the module 'forestprefs' by Thanatos.
	  I've stripped out everything apart from the code to allow auto levelling
	  in the forest when the player has enough experience for the next level.
	+ Changed the file name to suit its function.
	21/04/2012 - v1.0.1
	+ Level bug found and fixed by Aeolus. :)
	16/08/2018 - v1.0.2
	+ Added code for referrals and companions. Thanks Aeolus. :)
*/
function autolevel_getmoduleinfo()
{
	$info = array(
		"name"=>"Auto Level Up",
		"description"=>"Allow players to auto level up in the forest instead of fighting a Master.",
		"version"=>"1.0.2",
		"author"=>"`4Thanatos`2, modified by `@MarcTheSlayer",
		"download"=>"http://dragonprime.net/index.php?module=Downloads;sa=dlview;id=1447",
		"category"=>"Forest",
		"settings"=>array(
			"levellimit"=>"Maximum level that can be gained:,int|15",
			"`2If you have modified your game to have more levels than 15 then enter the highest level here else leave it at 15.,note",
		),
		"prefs"=>array(
			"Fight Master Training,title",
				"user_autotrain"=>"Level up automatically?,bool",
		),
	);
	return $info;
}

function autolevel_install()
{
	module_addhook('village');
	module_addhook('battle-victory');
	return TRUE;
}

function autolevel_uninstall()
{
	return TRUE;
}

function autolevel_dohook($hookname,$args)
{
	if( get_module_pref('user_autotrain') != 1 ) return $args;

	global $session, $companions;

	switch($hookname)
	{
		case 'village':
			blocknav('train.php');
		break;

		case 'battle-victory':
			if( get_module_setting('levellimit') > $session['user']['level'] )
			{
				require_once('lib/experience.php');
				$reqexp = exp_for_next_level($session['user']['level'],$session['user']['dragonkills']);
				if( $session['user']['experience'] + $args['creatureexp'] >= $reqexp )
				{
					require_once('lib/increment_specialty.php');
					$session['user']['level']++;
					$session['user']['maxhitpoints']+=10;
					$session['user']['hitpoints'] = $session['user']['maxhitpoints'];
					$session['user']['soulpoints']+=5;
					$session['user']['attack']++;
					$session['user']['defense']++;
					output('`n`c`b`2-=-`@=-=`2-=- `@You Level Up! `2-=-`@=-=`2-=-`0`b`c`n');
					output("`#You advance to level `^%s`#!`n", $session['user']['level']);
					output("Your maximum hitpoints are now `^%s`#!`n", $session['user']['maxhitpoints']);
					output("You gain an attack point!`n");
					output("You gain a defense point!");
					modulehook('training-victory');

					// Reward referrals
					if( $session['user']['referer'] > 0 && ($session['user']['level'] >= getsetting('referminlevel',4) || $session['user']['dragonkills'] > 0) && $session['user']['refererawarded'] < 1 )
					{
						$sql = "UPDATE " . db_prefix('accounts') . " SET donation = donation+".getsetting('refereraward',25)." WHERE acctid = {$session['user']['referer']}";
						db_query($sql);
						$session['user']['refererawarded'] = 1;
						$subj = array("`%One of your referrals advanced!`0");
						$body = array("`&%s`# has advanced to level `^%s`#, and so you have earned `^%s`# points!", $session['user']['name'], $session['user']['level'], getsetting('refereraward', 25));
						systemmail($session['user']['referer'],$subj,$body);
					}

					// Level-Up companions
					if( getsetting("companionslevelup", 1) == true )
					{
						$newcompanions = $companions;
						foreach( $companions as $name => $companion )
						{
							$companion['attack'] = $companion['attack'] + $companion['attackperlevel'];
							$companion['defense'] = $companion['defense'] + $companion['defenseperlevel'];
							$companion['maxhitpoints'] = $companion['maxhitpoints'] + $companion['maxhitpointsperlevel'];
							$companion['hitpoints'] = $companion['maxhitpoints'];
							$newcompanions[$name] = $companion;
						}
						$companions = $newcompanions;
					}

					increment_specialty("`^");
					output_notl("`n`c`b`2-=-`@=-=`2-=-`@=-=`2-=-`@=-=`2-=-`@=-=`2-=-`@=-=`2-=-`0`b`c`n");
				}
			}
		break;
	}
	return $args;
}

function autolevel_run()
{
}
?>