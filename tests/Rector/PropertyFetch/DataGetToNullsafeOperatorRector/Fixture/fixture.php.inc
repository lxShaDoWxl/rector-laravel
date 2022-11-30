<?php

namespace RectorLaravel\Tests\Rector\PropertyFetch\DataGetToNullsafeOperatorRector\Fixture;

/** @var \RectorLaravel\Tests\Rector\PropertyFetch\DataGetToNullsafeOperatorRector\Source\SomeClass|null $value */
data_get($value, 'department.name.test');
data_get($value, 'department.name.test', '');


?>
-----
<?php

namespace RectorLaravel\Tests\Rector\PropertyFetch\DataGetToNullsafeOperatorRector\Fixture;

/** @var \RectorLaravel\Tests\Rector\PropertyFetch\DataGetToNullsafeOperatorRector\Source\SomeClass|null $value */
$value?->department?->name?->test;
$value?->department?->name?->test ?? '';


?>
