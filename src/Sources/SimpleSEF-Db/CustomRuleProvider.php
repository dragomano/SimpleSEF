<?php declare(strict_types=1);

use Cocur\Slugify\RuleProvider\DefaultRuleProvider;

class CustomRuleProvider extends DefaultRuleProvider
{
	private array $customRules = [
		// https://yandex.ru/support/nmaps/app_transliteration.html
		'russian' => [
			'Ё' => 'YO',
			'Щ' => 'SCH',
			'ЪЕ' => 'YE',
			'ЫЙ' => 'IY',
			'ИЙ' => 'IY',
			'ё' => 'yo',
			'щ' => 'sch',
			'ъе' => 'ye',
			'ый' => 'iy',
			'ий' => 'iy',
		]
	];

	public function getRules(string $ruleset): array
	{
		$this->rules['russian'] = array_merge($this->rules['russian'], $this->customRules['russian']);

		return parent::getRules($ruleset);
	}
}