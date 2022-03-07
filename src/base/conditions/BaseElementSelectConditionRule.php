<?php

namespace craft\base\conditions;

use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionInterface;
use craft\helpers\Cp;

/**
 * BaseElementSelectConditionRule provides a base implementation for element query condition rules that are composed of an element select input.
 *
 * @property int|null $elementId
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseElementSelectConditionRule extends BaseConditionRule
{
    /**
     * @inheritdoc
     */
    public static function supportsProjectConfig(): bool
    {
        return false;
    }

    /**
     * @var int|null
     * @see getElementId()
     * @see setElementId()
     */
    private ?int $_elementId = null;

    /**
     * Returns the element type that can be selected.
     *
     * @return string
     */
    abstract protected function elementType(): string;

    /**
     * Returns the element source(s) that the element can be selected from.
     *
     * @return array|null
     */
    protected function sources(): ?array
    {
        return null;
    }

    /**
     * Returns the element condition that filters which elements can be selected.
     *
     * @return ElementConditionInterface|null
     */
    protected function selectionCondition(): ?ElementConditionInterface
    {
        return null;
    }

    /**
     * Returns the criteria that determines which elements can be selected.
     *
     * @return array|null
     */
    protected function criteria(): ?array
    {
        return null;
    }

    /**
     * @return int|null
     */
    public function getElementId(): ?int
    {
        return $this->_elementId;
    }

    /**
     * @param int|string $elementId
     */
    public function setElementId(int|string $elementId): void
    {
        if (is_array($elementId)) {
            $elementId = reset($elementId);
        }

        $this->_elementId = $elementId ? (int)$elementId : null;
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'elementId' => $this->_elementId,
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        $element = $this->_element();
        return Cp::elementSelectHtml([
            'name' => 'elementId',
            'elements' => $element ? [$element] : [],
            'elementType' => $this->elementType(),
            'sources' => $this->sources(),
            'criteria' => $this->criteria(),
            'condition' => $this->selectionCondition(),
            'single' => true,
        ]);
    }

    /**
     * @return ElementInterface|null
     */
    private function _element(): ?ElementInterface
    {
        if (!$this->_elementId) {
            return null;
        }

        /** @var string|ElementInterface $elementType */
        $elementType = $this->elementType();
        return $elementType::find()
            ->id($this->_elementId)
            ->status(null)
            ->one();
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['elementId'], 'number'];
        return $rules;
    }

    /**
     * Returns whether the condition rule matches the given value.
     *
     * @param ElementInterface|ElementInterface[]|int|int[]|null $value
     * @return bool
     */
    protected function matchValue(mixed $value): bool
    {
        $elementId = $this->getElementId();

        if (!$elementId) {
            return true;
        }

        if (!$value) {
            return false;
        }

        if ($value instanceof ElementInterface) {
            return $value->id === $elementId;
        }

        if (is_numeric($value)) {
            return (int)$value === $elementId;
        }

        if (is_array($value)) {
            foreach ($value as $val) {
                if (
                    $val instanceof ElementInterface && $val->id === $elementId ||
                    is_numeric($val) && (int)$val === $elementId
                ) {
                    return true;
                }
            }
        }

        return false;
    }
}