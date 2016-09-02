<?php

namespace Pim\Component\Connector\Reader\File\Xlsx;

use Akeneo\Component\Batch\Item\FileInvalidItem;
use Akeneo\Component\Batch\Item\FlushableInterface;
use Akeneo\Component\Batch\Item\InvalidItemException;
use Akeneo\Component\Batch\Item\ItemReaderInterface;
use Akeneo\Component\Batch\Model\StepExecution;
use Akeneo\Component\Batch\Step\StepExecutionAwareInterface;
use Pim\Component\Connector\ArrayConverter\ArrayConverterInterface;
use Pim\Component\Connector\Exception\DataArrayConversionException;
use Pim\Component\Connector\Exception\InvalidItemFromViolationsException;
use Pim\Component\Connector\Reader\File\FlatFileIteratorFactory;
use Pim\Component\Connector\Reader\File\FlatFileIteratorInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Xlsx Reader
 *
 * @author    Marie Bochu <marie.bochu@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Reader implements ItemReaderInterface, StepExecutionAwareInterface, FlushableInterface
{
    /** @var FlatFileIteratorFactory */
    protected $flatFileIteratorFactory;

    /** ArrayConverterInterface */
    protected $converter;

    /** @var FlatFileIteratorInterface */
    protected $flatFileIterator;

    /** @var StepExecution */
    protected $stepExecution;

    /** @var array */
    protected $options;

    /**
     * @param FlatFileIteratorFactory $flatFileIteratorFactory
     * @param ArrayConverterInterface $converter
     * @param array                   $options
     */
    public function __construct(
        FlatFileIteratorFactory $flatFileIteratorFactory,
        ArrayConverterInterface $converter,
        array $options = []
    ) {
        $this->flatFileIteratorFactory = $flatFileIteratorFactory;
        $this->converter = $converter;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function read()
    {
        $filePath = null;
        if (null === $this->flatFileIterator) {
            $jobParameters = $this->stepExecution->getJobParameters();
            $filePath = $jobParameters->get('filePath');
            $this->flatFileIterator = $this->flatFileIteratorFactory->create($filePath, $this->options);
            $this->flatFileIterator->rewind();
        }

        $this->flatFileIterator->next();

        if ($this->flatFileIterator->valid() && null !== $this->stepExecution) {
            $this->stepExecution->incrementSummaryInfo('item_position');
        }

        $data = $this->flatFileIterator->current();

        if (null === $data) {
            return null;
        }

        $headers = $this->flatFileIterator->getHeaders();

        $countHeaders = count($headers);
        $countData = count($data);

        $this->checkColumnNumber($countHeaders, $countData, $data, $filePath);

        if ($countHeaders > $countData) {
            $missingValuesCount = $countHeaders - $countData;
            $missingValues = array_fill(0, $missingValuesCount, '');
            $data = array_merge($data, $missingValues);
        }

        $item = array_combine($this->flatFileIterator->getHeaders(), $data);

        try {
            $item = $this->converter->convert($item, $this->getArrayConverterOptions());
        } catch (DataArrayConversionException $e) {
            $this->skipItemFromConversionException($item, $e);
        }

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->flatFileIterator = null;
    }

    /**
     * Returns the options for array converter. It can be overridden in the sub classes.
     *
     * @return array
     */
    protected function getArrayConverterOptions()
    {
        return [];
    }

    /**
     * @param array                        $item
     * @param DataArrayConversionException $exception
     *
     * @throws InvalidItemException
     * @throws InvalidItemFromViolationsException
     */
    protected function skipItemFromConversionException(array $item, DataArrayConversionException $exception)
    {
        if (null !== $this->stepExecution) {
            $this->stepExecution->incrementSummaryInfo('skip');
        }

        if (null !== $exception->getViolations()) {
            throw new InvalidItemFromViolationsException(
                $exception->getViolations(),
                new FileInvalidItem($item, ($this->stepExecution->getSummaryInfo('item_position'))),
                [],
                0,
                $exception
            );
        }

        throw new InvalidItemException(
            $exception->getMessage(),
            new FileInvalidItem($item, ($this->stepExecution->getSummaryInfo('item_position'))),
            [],
            0,
            $exception
        );
    }

    /**
     * @param int    $countHeaders
     * @param int    $countData
     * @param string $data
     * @param string $filePath
     *
     * @throws InvalidItemException
     */
    protected function checkColumnNumber($countHeaders, $countData, $data, $filePath)
    {
        if ($countHeaders < $countData) {
            throw new InvalidItemException(
                'pim_connector.steps.file_reader.invalid_item_columns_count',
                new FileInvalidItem($data, ($this->stepExecution->getSummaryInfo('item_position'))),
                [
                    '%totalColumnsCount%' => $countHeaders,
                    '%itemColumnsCount%'  => $countData,
                    '%filePath%'          => $filePath,
                    '%lineno%'            => $this->flatFileIterator->key()
                ]
            );
        }
    }
}
