<?php

$options = array(
	'alwaysNotifyPlayerJoined' => array( 'type' => 'bool', 'default' => 'false' ),
	'alwaysNotifyPlayerLeft' => array( 'type' => 'bool', 'default' => 'false' ),
	'allowThirdPersonPlayer' => array( 'type' => 'bool', 'default' => 'false' ),
	'globalVoiceChat' => array( 'type' => 'bool', 'default' => 'false' ),
	'ShowMapPlayerLocation' => array( 'type' => 'bool', 'default' => 'false' ),
	'noTributeDownloads' =>  array( 'type' => 'bool', 'default' => 'false' ),
	'proximityChat' =>  array( 'type' => 'bool', 'default' => 'false' ),
	'serverPVE' =>  array( 'type' => 'bool', 'default' => 'false' ),
	'serverHardcore' =>  array( 'type' => 'bool', 'default' => 'false' ),
	'serverForceNoHud' =>  array( 'type' => 'bool', 'default' => 'false' ),
	'DisableStructureDecayPvE' =>  array( 'type' => 'bool', 'default' => 'false' ),
	'DisableDinoDecayPvE' =>  array( 'type' => 'bool', 'default' => 'false' ),
	'AllowFlyerCarryPvE' =>  array( 'type' => 'bool', 'default' => 'false' ),
	'MaxStructuresInRange' => array( 'type' => 'int' ),
	'MaxPlayers' => array( 'type' => 'int', 'default' => 200 ),
	'DifficultyOffset' => array( 'type' => 'float', 'max' => 1, 'default' => '0.20' ),
	'ServerPassword' => array( 'type' => 'string', 'required' => true ),
	'ServerAdminPassword' => array( 'type' => 'string', 'required' => true ),
	'SpectatorPassword' => array( 'type' => 'string' ),
	'DayCycleSpeedScale' => array( 'type' => 'float' ),
	'NightTimeSpeedScale' => array( 'type' => 'float' ),
	'DayTimeSpeedScale' => array( 'type' => 'float' ),
	'DinoDamageMultiplier' => array( 'type' => 'float' ),
	'PlayerDamageMultiplier' => array( 'type' => 'float' ),
	'StructureDamageMultiplier' => array( 'type' => 'float' ),
	'PlayerResistanceMultiplier' => array( 'type' => 'float' ),
	'DinoResistanceMultiplier' => array( 'type' => 'float' ),
	'StructureResistanceMultiplier' => array( 'type' => 'float' ),
	'XPMultiplier' => array( 'type' => 'float' ),
	'PvEStructureDecayPeriodMultiplier' => array( 'type' => 'float' ),
	'PvEStructureDecayDestructionPeriod' => array( 'type' => 'int' ),
	'TamingSpeedMultiplier' => array( 'type' => 'float' ),
	'HarvestAmountMultiplier' => array( 'type' => 'float' ),
	'HarvestHealthMultiplier' => array( 'type' => 'float' ),
	'ResourcesRespawnPeriodMultiplier' => array( 'type' => 'float' ),
	'PlayerCharacterWaterDrainMultiplier' => array( 'type' => 'float' ),
	'PlayerCharacterFoodDrainMultiplier' => array( 'type' => 'float' ),
	'PlayerCharacterStaminaDrainMultiplier' => array( 'type' => 'float' ),
	'PlayerCharacterHealthRecoveryMultiplier' => array( 'type' => 'float' ),
	'DinoCharacterFoodDrainMultiplier' => array( 'type' => 'float' ),
	'DinoCharacterStaminaDrainMultiplier' => array( 'type' => 'float' ),
	'DinoCharacterHealthRecoveryMultiplier' => array( 'type' => 'float' ),
	'DinoCountMultiplier' => array( 'type' => 'float' ),
	'AllowCaveBuildingPvE' => array( 'type' => 'bool', 'default' => 'false' ),
	'BanListURL' => array( 'type' => 'string' ),
	'PvPStructureDecay' => array( 'type' => 'bool', 'default' => 'false' ),
	'RCONEnabled' => array( 'type' => 'bool', 'default' => true, ),
	'RCONPort' => array( 'type' => 'int', 'max' => 32767, 'default' => 32330 ),
	'ServerCrosshair' => array( 'type' => 'bool', 'default' => 'false' ),
	'AltSaveDirectoryName' => array( 'type' => 'string', 'required' => 'true' ),
	'PreventDownloadSurvivors' => array( 'type' => 'bool', 'default' => 'false' ),
	'PreventDownloadDinos' => array( 'type' => 'bool', 'default' => 'false' ),
	'PreventDownloadItems' => array( 'type' => 'bool', 'default' => 'false' ),
	'OnlyAllowSpecifiedEngrams' => array( 'type' => 'bool', 'default' => 'false' ),
	'EnablePVPGamma' => array( 'type' => 'bool', 'default' => 'false' ),
	'ClampResourceHarvestDamage' => array( 'type' => 'bool', 'default' => 'false' ),
	'KickIdlePlayersPeriod' => array( 'type' => 'int', 'default' => 2400 ),
	'AutoSavePeriodMinutes' => array( 'type' => 'int', 'default' => 15 ),
	'PvPZoneStructureDamageMultiplier' => array( 'type' => 'float', 'default' => 6 ),
	'ActiveMods' => array( 'type' => 'string', 'default' => '' ),
	'MapModID' => array( 'type' => 'string', 'default' => '' ),
	'GameModIds' => array( 'type' => 'string', 'default' => '' ),
	'MaxTamedDinos' => array( 'type' => 'int', 'default' => 4000 )
);

ksort($options);

$advanced = array(
	'OverrideEngramEntries' => array(
		'type' => 'params',
		'multi' => true,
		'params' => array(
			'EngramIndex' => array( 'type' => 'int', 'list' => 'Engrams' ),
			'EngramHidden' => array( 'type' => 'bool' ),
			'EngramPointsCost' => array( 'type' => 'int' ),
			'EngramLevelRequirement' => array( 'type' => 'int' ),
			'RemoveEngramPreReq' => array( 'type' => 'bool' )
		)
	),
	'DinoSpawnWeightMultipliers' => array(
		'type' => 'params',
		'multi' => true,
		'params' => array(
			'DinoNameTag' => array( 'type' => 'string', 'list' => 'DinoTags' ),
			'SpawnWeightMultiplier' => array( 'type' => 'float' ),
			'OverrideSpawnLimitPercentage' => array( 'type' => 'bool' ),
			'SpawnLimitPercentage' => array( 'type' => 'float' )
		)
	),
	'OverridePlayerLevelEngramPoints' => array( 'type' => 'int', 'multi' => true ),
	'GlobalSpoilingTimeMultiplier' => array( 'type' => 'float' ),
	'GlobalItemDecompositionTimeMultiplier' => array( 'type' => 'float' ),
	'GlobalCorpseDecompositionTimeMultiplier' => array( 'type' => 'float' ),
	'HarvestResourceItemAmountClassMultipliers' => array(
		'type' => 'params',
		'multi' => true,
		'params' => array(
			'ClassName' => array( 'type' => 'string', 'list' => 'Resources' ),
			'Multiplier' => array( 'type' => 'float' )
		)
	),
	'OverrideMaxExperiencePointsPlayer' => array( 'type' => 'int' ),
	'OverrideMaxExperiencePointsDino' => array( 'type' => 'int' ),
	'PreventDinoTameClassNames' => array( 'type' => 'string', 'list' => 'DinoClasses' ),
	'NPCReplacements' => array(
		'type' => 'params',
		'multi' => true,
		'params' => array(
			'FromClassName' => array( 'type' => 'string', 'list' => 'DinoClasses' ),
			'ToClassName' => array( 'type' => 'string', 'list' => 'DinoClasses' )
		)
	),
	'ResourceNoReplenishRadiusPlayers' => array( 'type' => 'float', 'default' => 1 ),
	'ResourceNoReplenishRadiusStructures' => array( 'type' => 'float', 'default' => 1 ),
	'IncreasePvPRespawnInterval' => array( 'type' => 'bool', 'default' => 'false' ),
	'IncreasePvPRespawnIntervalCheckPeriod' => array( 'type' => 'int', 'default' => 300 ),
	'IncreasePvPRespawnIntervalMultiplier' => array( 'type' => 'float', 'default' => 2 ),
	'IncreasePvPRespawnIntervalBaseAmount' => array( 'type' => 'float', 'default' => 60 ),
	'AutoPvETimer' => array( 'type' => 'bool', 'default' => 'false' ),
	'AutoPvEUseSystemTime' => array( 'type' => 'bool', 'default' => 'false' ),
	'AutoPvEStartTimeSeconds' => array( 'type' => 'int', 'max' => 86400, 'default' => 43200 ),
	'AutoPvEStopTimeSeconds' => array( 'type' => 'int', 'max' => 86400, 'default' => 43200 ),
	'bPvEDisableFriendlyFire' => array( 'type' => 'bool', 'default' => 'false'),
	'PerPlatformMaxStructuresMultiplier' => array( 'type' => 'float', 'default' => 1 ),
	'MaxPlatformSaddleStructureLimit' => array( 'type' => 'int' ),
	'MatingIntervalMultiplier' => array( 'type' => 'float', 'default' => 1 ),
	'EggHatchSpeedMultiplier' => array( 'type' => 'float', 'default' => 1 ),
	'BabyMatureSpeedMultiplier' => array( 'type' => 'float', 'default' => 1 ),
	'BabyFoodConsumptionSpeedMultiplier' => array( 'type' => 'float', 'default' => 1 ),
	'CropGrowthSpeedMultiplier' => array( 'type' => 'float', 'default' => 1 ),
	'LayEggIntervalMultiplier' => array( 'type' => 'float', 'default' => 1 ),
	'PoopIntervalMultiplier' => array( 'type' => 'float', 'default' => 1 ),
	'CropDecaySpeedMultiplier' => array( 'type' => 'float', 'default' => 1 ),
	'StructureDamageRepairCooldown' => array( 'type' => 'int', 'default' => 180 ),
	'bPvEAllowTribeWar' => array( 'type' => 'bool', 'default' => 'true' ),
	'bPvEAllowTribeWarCancel' => array( 'type' => 'bool', 'default' => 'false' ),
	'bPassiveDefensesDamageRiderlessDinos' => array( 'type' => 'bool', 'default' => 'false' ),
	'CustomRecipeEffectivenessMultiplier' => array( 'type' => 'float', 'default' => 1 ),
	'CustomRecipeSkillMultiplier' => array( 'type' => 'float', 'default' => 1 ),
	'DinoHarvestingDamageMultiplier' => array( 'type' => 'float', 'default' => 1 ),
	'PlayerHarvestingDamageMultiplier' => array( 'type' => 'float', 'default' => 1 ),
	'DinoTurretDamageMultiplier' => array( 'type' => 'float', 'default' => 1 ),
	'bDisableLootCrates' => array( 'type' => 'bool', 'default' => 'false' )
);

$lists = array(
	'DinoClasses' => array(),
	'Resources' => array(),
	'DinoTags' => array(),
	'Engrams' => array()
);

?>
