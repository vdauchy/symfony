<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Tests\Constraints;

use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LengthValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class LengthValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new LengthValidator();
    }

    public function testNullIsValid()
    {
        $this->validator->validate(null, new Length(['value' => 6]));

        $this->assertNoViolation();
    }

    /**
     * @group legacy
     */
    public function testAllowEmptyString()
    {
        $this->validator->validate('', new Length(['value' => 6, 'allowEmptyString' => true]));

        $this->assertNoViolation();
    }

    public function testEmptyStringIsInvalid()
    {
        $this->validator->validate('', new Length([
            'value' => $limit = 6,
            'exactMessage' => 'myMessage',
        ]));

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', '""')
            ->setParameter('{{ limit }}', $limit)
            ->setInvalidValue('')
            ->setPlural($limit)
            ->setCode(Length::NOT_EQUAL_LENGTH_ERROR)
            ->assertRaised();
    }

    public function testExpectsStringCompatibleType()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate(new \stdClass(), new Length(['value' => 5]));
    }

    public static function getThreeOrLessCharacters()
    {
        return [
            [12],
            ['12'],
            ['üü'],
            ['éé'],
            [123],
            ['123'],
            ['üüü'],
            ['ééé'],
        ];
    }

    public static function getFourCharacters()
    {
        return [
            [1234],
            ['1234'],
            ['üüüü'],
            ['éééé'],
        ];
    }

    public static function getFiveOrMoreCharacters()
    {
        return [
            [12345],
            ['12345'],
            ['üüüüü'],
            ['ééééé'],
            [123456],
            ['123456'],
            ['üüüüüü'],
            ['éééééé'],
        ];
    }

    public static function getOneCharset()
    {
        return [
            ['é', 'utf8', true],
            ["\xE9", 'CP1252', true],
            ["\xE9", 'XXX', false],
            ["\xE9", 'utf8', false],
        ];
    }

    public static function getThreeCharactersWithWhitespaces()
    {
        return [
            ["\x20ccc"],
            ["\x09c\x09c"],
            ["\x0Accc\x0A"],
            ["ccc\x0D\x0D"],
            ["\x00ccc\x00"],
            ["\x0Bc\x0Bc\x0B"],
        ];
    }

    /**
     * @dataProvider getFiveOrMoreCharacters
     */
    public function testValidValuesMin($value)
    {
        $constraint = new Length(['min' => 5]);
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider getThreeOrLessCharacters
     */
    public function testValidValuesMax($value)
    {
        $constraint = new Length(['max' => 3]);
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider getFourCharacters
     */
    public function testValidValuesExact($value)
    {
        $constraint = new Length(4);
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider getThreeCharactersWithWhitespaces
     */
    public function testValidNormalizedValues($value)
    {
        $constraint = new Length(['min' => 3, 'max' => 3, 'normalizer' => 'trim']);
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider getThreeOrLessCharacters
     */
    public function testInvalidValuesMin($value)
    {
        $constraint = new Length([
            'min' => 4,
            'minMessage' => 'myMessage',
        ]);

        $this->validator->validate($value, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', '"'.$value.'"')
            ->setParameter('{{ limit }}', 4)
            ->setInvalidValue($value)
            ->setPlural(4)
            ->setCode(Length::TOO_SHORT_ERROR)
            ->assertRaised();
    }

    /**
     * @requires PHP 8
     *
     * @dataProvider getThreeOrLessCharacters
     */
    public function testInvalidValuesMinNamed($value)
    {
        $constraint = eval('return new \Symfony\Component\Validator\Constraints\Length(min: 4, minMessage: "myMessage");');

        $this->validator->validate($value, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', '"'.$value.'"')
            ->setParameter('{{ limit }}', 4)
            ->setInvalidValue($value)
            ->setPlural(4)
            ->setCode(Length::TOO_SHORT_ERROR)
            ->assertRaised();
    }

    /**
     * @dataProvider getFiveOrMoreCharacters
     */
    public function testInvalidValuesMax($value)
    {
        $constraint = new Length([
            'max' => 4,
            'maxMessage' => 'myMessage',
        ]);

        $this->validator->validate($value, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', '"'.$value.'"')
            ->setParameter('{{ limit }}', 4)
            ->setInvalidValue($value)
            ->setPlural(4)
            ->setCode(Length::TOO_LONG_ERROR)
            ->assertRaised();
    }

    /**
     * @requires PHP 8
     *
     * @dataProvider getFiveOrMoreCharacters
     */
    public function testInvalidValuesMaxNamed($value)
    {
        $constraint = eval('return new \Symfony\Component\Validator\Constraints\Length(max: 4, maxMessage: "myMessage");');

        $this->validator->validate($value, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', '"'.$value.'"')
            ->setParameter('{{ limit }}', 4)
            ->setInvalidValue($value)
            ->setPlural(4)
            ->setCode(Length::TOO_LONG_ERROR)
            ->assertRaised();
    }

    /**
     * @dataProvider getThreeOrLessCharacters
     */
    public function testInvalidValuesExactLessThanFour($value)
    {
        $constraint = new Length([
            'min' => 4,
            'max' => 4,
            'exactMessage' => 'myMessage',
        ]);

        $this->validator->validate($value, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', '"'.$value.'"')
            ->setParameter('{{ limit }}', 4)
            ->setInvalidValue($value)
            ->setPlural(4)
            ->setCode(Length::NOT_EQUAL_LENGTH_ERROR)
            ->assertRaised();
    }

    /**
     * @requires PHP 8
     *
     * @dataProvider getThreeOrLessCharacters
     */
    public function testInvalidValuesExactLessThanFourNamed($value)
    {
        $constraint = eval('return new \Symfony\Component\Validator\Constraints\Length(exactly: 4, exactMessage: "myMessage");');

        $this->validator->validate($value, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', '"'.$value.'"')
            ->setParameter('{{ limit }}', 4)
            ->setInvalidValue($value)
            ->setPlural(4)
            ->setCode(Length::NOT_EQUAL_LENGTH_ERROR)
            ->assertRaised();
    }

    /**
     * @dataProvider getFiveOrMoreCharacters
     */
    public function testInvalidValuesExactMoreThanFour($value)
    {
        $constraint = new Length([
            'min' => 4,
            'max' => 4,
            'exactMessage' => 'myMessage',
        ]);

        $this->validator->validate($value, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', '"'.$value.'"')
            ->setParameter('{{ limit }}', 4)
            ->setInvalidValue($value)
            ->setPlural(4)
            ->setCode(Length::NOT_EQUAL_LENGTH_ERROR)
            ->assertRaised();
    }

    /**
     * @dataProvider getOneCharset
     */
    public function testOneCharset($value, $charset, $isValid)
    {
        $constraint = new Length([
            'min' => 1,
            'max' => 1,
            'charset' => $charset,
            'charsetMessage' => 'myMessage',
        ]);

        $this->validator->validate($value, $constraint);

        if ($isValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation('myMessage')
                ->setParameter('{{ value }}', '"'.$value.'"')
                ->setParameter('{{ charset }}', $charset)
                ->setInvalidValue($value)
                ->setCode(Length::INVALID_CHARACTERS_ERROR)
                ->assertRaised();
        }
    }
}
