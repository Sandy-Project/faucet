<?php

function getCoinName($cointype)
{
    switch ($cointype) {
        case "BTC":
            return "bitcoin";
        case "LTC":
            return "litecoin";
        case "NMC":
            return "namecoin";
        case "BTCTEST":
            return "bitcoin";
        case "NVC":
            return "novacoin";
        case "PPC":
            return "peercoin";
        case "DOGE":
            return "dogecoin";
        case "EAC":
            return "Earthcoin";
        default:
            return "Earthcoin";
    }
}

function getSubunitName($cointype)
{ // plural!!!
    switch ($cointype) {
        case "BTC":
            return "satoshis";
        case "LTC":
            return "microcoins"; // 1000 * 1000
        case "NMC":
            return "microcoins"; // 1000 * 1000
        case "BTCTEST":
            return "satoshis";
        case "NVC":
            return "millicoins"; // 1000
        case "PPC":
            return "microcoins"; // 1000 * 1000
        case "DOGE":
            return "microdoges"; // 1000 * 1000
        case "EAC":
            return "microcoins";
        default:
            return "microcoins";
    }
}

function getSubunitDivider($cointype)
{
    switch ($cointype) {
        case "BTC":
            return 100 * 1000 * 1000;
        case "LTC":
            return 1000 * 1000;
        case "NMC":
            return 1000 * 1000;
        case "BTCTEST":
            return 100 * 1000 * 1000;
        case "NVC":
            return 1000;
        case "PPC":
            return 1000 * 1000;
        case "DOGE":
            return 1000 * 1000;
        case "EAC":
            return 1000 * 1000;
        default:
            return 1000 * 1000;
    }
}
