<?php

namespace spec\Pim\Component\Connector\Reader\File\Xlsx;

use Akeneo\Component\Batch\Job\JobParameters;
use Akeneo\Component\Batch\Model\StepExecution;
use PhpSpec\ObjectBehavior;
use Pim\Component\Connector\ArrayConverter\ArrayConverterInterface;
use Pim\Component\Connector\Exception\DataArrayConversionException;
use Pim\Component\Connector\Reader\File\FlatFileIteratorFactory;
use Pim\Component\Connector\Reader\File\FlatFileIterator;
use Prophecy\Argument;
use Symfony\Component\Validator\ConstraintViolationList;

class ReaderSpec extends ObjectBehavior
{
    function let(
        FlatFileIteratorFactory $flatFileIteratorFactory,
        ArrayConverterInterface $converter,
        StepExecution $stepExecution
    ) {
        $this->beConstructedWith($flatFileIteratorFactory, $converter);
        $this->setStepExecution($stepExecution);
    }

    function it_read_xlsx_file(
        $flatFileIteratorFactory,
        $converter,
        $stepExecution,
        FlatFileIterator $flatFileIterator,
        JobParameters $jobParameters
    ) {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR .
            DIRECTORY_SEPARATOR . 'features' .
            DIRECTORY_SEPARATOR . 'Context' .
            DIRECTORY_SEPARATOR . 'fixtures' .
            DIRECTORY_SEPARATOR . 'product_with_carriage_return.xlsx';

        $stepExecution->getJobParameters()->willReturn($jobParameters);
        $jobParameters->get('filePath')->willReturn($filePath);

        $data = [
            'sku'  => 'SKU-001',
            'name' => 'door',
        ];

        $flatFileIteratorFactory->create($filePath, [])->willReturn($flatFileIterator);

        $flatFileIterator->getHeaders()->willReturn(['sku', 'name']);
        $flatFileIterator->rewind()->shouldBeCalled();
        $flatFileIterator->next()->shouldBeCalled();
        $flatFileIterator->valid()->willReturn(true);
        $flatFileIterator->current()->willReturn($data);
        $converter->convert($data, Argument::any())->willReturn($data);

        $stepExecution->incrementSummaryInfo('item_position')->shouldBeCalled();

        $this->read()->shouldReturn($data);
    }

    function it_skips_an_item_in_case_of_conversion_error(
        $flatFileIteratorFactory,
        $converter,
        $stepExecution,
        FlatFileIterator $flatFileIterator,
        JobParameters $jobParameters
    ) {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR .
            DIRECTORY_SEPARATOR . 'features' .
            DIRECTORY_SEPARATOR . 'Context' .
            DIRECTORY_SEPARATOR . 'fixtures' .
            DIRECTORY_SEPARATOR . 'product_with_carriage_return.xlsx';

        $stepExecution->getJobParameters()->willReturn($jobParameters);
        $jobParameters->get('filePath')->willReturn($filePath);

        $data = [
            'sku'  => 'SKU-001',
            'name' => 'door',
        ];

        $stepExecution->getSummaryInfo('item_position')->shouldBeCalled();

        $flatFileIteratorFactory->create($filePath, [])->willReturn($flatFileIterator);

        $flatFileIterator->getHeaders()->willReturn(['sku', 'name']);
        $flatFileIterator->rewind()->shouldBeCalled();
        $flatFileIterator->next()->shouldBeCalled();
        $flatFileIterator->isHeader()->willReturn(false);
        $flatFileIterator->valid()->willReturn(true);
        $flatFileIterator->current()->willReturn($data);
        $converter->convert($data, Argument::any())->willReturn($data);

        $stepExecution->incrementSummaryInfo('item_position')->shouldBeCalled();

        $stepExecution->incrementSummaryInfo("skip")->shouldBeCalled();
        $converter->convert($data, Argument::any())->willThrow(
            new DataArrayConversionException('message', 0, null, new ConstraintViolationList())
        );

        $this->shouldThrow('Pim\Component\Connector\Exception\InvalidItemFromViolationsException')->during('read');
    }


}
