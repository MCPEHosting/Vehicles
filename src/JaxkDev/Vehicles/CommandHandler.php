<?php
/*
 * Vehicles, PocketMine-MP Plugin.
 *
 * Licensed under the Open Software License version 3.0 (OSL-3.0)
 * Copyright (C) 2019-2020 JaxkDev
 *
 * Twitter :: @JaxkDev
 * Discord :: JaxkDev#8860
 * Email   :: JaxkDev@gmail.com
 */

declare(strict_types=1);

namespace JaxkDev\Vehicles;

use pocketmine\Player;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as C;
use pocketmine\command\ConsoleCommandSender;

class CommandHandler
{
	/** @var Main */
	private $plugin;
	
	/** @var string */
	private $prefix;

	public function __construct(Main $plugin)
	{
		$this->plugin = $plugin;
		$this->prefix = Main::$prefix;
	}
	
	//TODO part of rewrite for handling nbt+id's

	/**
	 * @internal 
	 * Used directly from pmmp, no other plugins should be passing commands here (if really needed, dispatch command from server).
	 *
	 * @param CommandSender|Player $sender
	 * @param array $args
	 */
	function handleCommand(CommandSender $sender, array $args): void{
		if($sender instanceof ConsoleCommandSender){
			$sender->sendMessage($this->prefix.C::RED."Commands for Vehicles cannot be run from console.");
			return;
		}
		if(count($args) == 0){
			$sender->sendMessage($this->prefix.C::RED."Usage: /vehicles help");
			return;
		}
		$subCommand = $args[0];
		array_shift($args);
		switch($subCommand){
			case 'help':
				$sender->sendMessage($this->prefix.C::RED."-- HELP --");
				$sender->sendMessage($this->prefix.C::GOLD."/vehicles help");
				$sender->sendMessage($this->prefix.C::GOLD."/vehicles credits");
				$sender->sendMessage($this->prefix.C::GOLD."/vehicles spawn [type]");
				$sender->sendMessage($this->prefix.C::GOLD."/vehicles types/list");
				$sender->sendMessage($this->prefix.C::GOLD."/vehicles remove");
				$sender->sendMessage($this->prefix.C::GOLD."/vehicles lock/unlock");
				$sender->sendMessage($this->prefix.C::GOLD."/vehicles giveaway");
				break;
			case 'credits':
			case 'creds':
				$sender->sendMessage($this->prefix.C::GOLD."--- Credits ---");
				$sender->sendMessage($this->prefix.C::GREEN."Developer: ".C::RED."JaxkDev\n".$this->prefix.C::GREEN."Testers: ".C::RED."Kevin (@kevinishawesome), 'Simule City' beta players.");
				break;
			case 'list':
			case 'types':
			case 'type':
				$sender->sendMessage($this->prefix.C::RED."To spawn: /vehicles spawn <type>");
				$sender->sendMessage($this->prefix.C::AQUA."Vehicles's Available:\n- ".join("\n- ", array_keys($this->plugin->factory->getAllVehicleData())));
				break;
			case 'spawn':
			case 'create':
			case 'new':
				if(!$sender->hasPermission("vehicles.command.spawn")){
					$sender->sendMessage($this->prefix.C::RED."You do not have permission to use that command.");
					return;
				}
				if(count($args) === 0){
					$sender->sendMessage($this->prefix.C::RED."Usage: /vehicles spawn (Type)");
					$sender->sendMessage($this->prefix.C::AQUA."Vehicles's Available:\n- ".join("\n- ", array_keys($this->plugin->factory->getAllVehicleData())));
					return;
				}
				if($this->plugin->factory->getVehicleData($args[0]) !== null){
					$this->plugin->factory->spawnVehicle($this->plugin->factory->getVehicleData($args[0]), $sender->getLevel(), $sender->asVector3());
				}
				else{
					$sender->sendMessage($this->prefix.C::RED."\"".$args[0]."\" does not exist.");
					return;
				}
				$sender->sendMessage($this->prefix.C::GOLD."\"".$args[0]."\" Created.");
				break;
			case 'del':
			case 'rem':
			case 'delete':
			case 'remove':
				if(!$sender->hasPermission("vehicles.command.remove")){
					$sender->sendMessage($this->prefix.C::RED."You do not have permission to use that command.");
					return;
				}
				$this->plugin->interactCommands[strtolower($sender->getName())] = ["remove", [$args]];
				$sender->sendMessage($this->prefix.C::GREEN."Tap the vehicle you wish to remove.");
				break;
			case 'lock':
				//No one is allowed in.
				if(!$sender->hasPermission("vehicles.command.lock")){
					$sender->sendMessage($this->prefix.C::RED."You do not have permission to use that command.");
					return;
				}
				if(!array_key_exists($sender->getRawUniqueId(), Main::$inVehicle)){
					$sender->sendMessage($this->prefix.C::GREEN."Tap the vehicle you wish to lock. (You must be the owner to lock)");
					$this->plugin->interactCommands[strtolower($sender->getName())] = ["lock", []];
					return;
				}
				/** @var Vehicle $vehicle */
				$vehicle = Main::$inVehicle[$sender->getRawUniqueId()];
				if($vehicle->getOwner() === null){
					$sender->sendMessage($this->prefix.C::RED."This vehicle has no owner, to claim it jump in the driver seat.");
					return;
				}
				if($sender->getUniqueId()->equals($vehicle->getOwner())){
					if($vehicle->isLocked()) {
						$sender->sendMessage($this->prefix . C::RED . "This vehicle is already locked.");
						return;
					}
					$vehicle->setLocked(true);
					$sender->sendMessage($this->prefix.C::GOLD."This vehicle has been locked.");
					return;
				}
				$sender->sendMessage($this->prefix.C::RED."You are not the owner of this vehicle.");
				break;
			case 'unlock':
				//Anyone can enter the vehicle.
				if(!$sender->hasPermission("vehicles.command.unlock")){
					$sender->sendMessage($this->prefix.C::RED."You do not have permission to use that command.");
					return;
				}
				if(!array_key_exists($sender->getRawUniqueId(), Main::$inVehicle)){
					$sender->sendMessage($this->prefix.C::GREEN."Tap the vehicle you wish to un-lock. (You must be the owner to un-lock)");
					$this->plugin->interactCommands[strtolower($sender->getName())] = ["un-lock", []];
					return;
				}
				/** @var Vehicle $vehicle */
				$vehicle = Main::$inVehicle[$sender->getRawUniqueId()];
				if($vehicle->getOwner() === null){
					$sender->sendMessage($this->prefix.C::RED."This vehicle has no owner, to claim it jump in the driver seat.");
					return;
				}
				if($sender->getUniqueId()->equals($vehicle->getOwner())){
					if(!$vehicle->isLocked()) {
						$sender->sendMessage($this->prefix . C::RED . "This vehicle is already un-locked.");
						return;
					}
					$vehicle->setLocked(false);
					$sender->sendMessage($this->prefix.C::GOLD."This vehicle has been un-locked.");
					return;
				}
				$sender->sendMessage($this->prefix.C::RED."You are not the owner of this vehicle.");
				break;
			case 'giveaway':
				if(!$sender->hasPermission("vehicles.command.giveaway")){
					$sender->sendMessage($this->prefix.C::RED."You do not have permission to use that command.");
					return;
				}
				if(!array_key_exists($sender->getRawUniqueId(), Main::$inVehicle)){
					$sender->sendMessage($this->prefix.C::GREEN."Tap the vehicle you wish to giveaway. (You must be the owner)");
					$this->plugin->interactCommands[strtolower($sender->getName())] = ["giveaway", []];
					return;
				}
				/** @var Vehicle $vehicle */
				$vehicle = Main::$inVehicle[$sender->getRawUniqueId()];
				if($vehicle->getOwner() === null){
					$sender->sendMessage($this->prefix.C::RED."This vehicle has no owner.");
					return;
				}
				if($sender->getUniqueId()->equals($vehicle->getOwner())){
					$vehicle->removeOwner();
					$sender->sendMessage($this->prefix.C::GOLD."This vehicle has been given away, next person to drive it will become owner.");
					return;
				}
				$sender->sendMessage($this->prefix.C::RED."You are not the owner of this vehicle, so you cannot give it away.");
				break;
			default:
				$sender->sendMessage($this->prefix.C::RED."Unknown command, please check ".C::GREEN."/vehicles help".C::RED." For all available commands.");
		}
	}
}
