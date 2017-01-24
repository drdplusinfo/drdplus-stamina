# Stamina for [DrD+](http://www.altar.cz/drdplus/)

[![Build Status](https://travis-ci.org/jaroslavtyc/drd-plus-stamina.svg?branch=master)](https://travis-ci.org/jaroslavtyc/drd-plus-stamina)
[![Test Coverage](https://codeclimate.com/github/jaroslavtyc/drd-plus-stamina/badges/coverage.svg)](https://codeclimate.com/github/jaroslavtyc/drd-plus-stamina/coverage)
[![License](https://poser.pugx.org/drd-plus/stamina/license)](https://packagist.org/packages/drd-plus/stamina)

What is your current fatigue? And a malus caused by it? How well did you rested?

### Warning: there is a difference against PPH v1.0
In PPH on page 117 left column is an example with little catty Mrrr and its less-than-one fatigue boundary.

As a result of her endurance -29 should be 2/6 chance (66.7 %) she dies on birth, but the example gives her
1 stamina point instead and her grid of fatigue has just a single row with a single field.

This `drd-plus/stamina` library does *NOT* allows such thing so on endurance of -29 there is 66.7 % the creature
will simply die.