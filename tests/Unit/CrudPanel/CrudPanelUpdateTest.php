<?php

namespace Backpack\CRUD\Tests\Unit\CrudPanel;

use Backpack\CRUD\Tests\Unit\Models\User;
use Backpack\CRUD\Tests\Unit\Models\AccountDetails;
use Faker\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class CrudPanelUpdateTest extends BaseDBCrudPanelTest
{
    private $userInputFields = [
        [
            'name' => 'id',
            'type' => 'hidden',
        ], [
            'name' => 'name',
        ], [
            'name' => 'email',
            'type' => 'email',
        ], [
            'name' => 'password',
            'type' => 'password',
        ],
    ];

    private $expectedUpdatedFields = [
        'id' => [
            'name'  => 'id',
            'type'  => 'hidden',
            'label' => 'Id',
        ],
        'name' => [
            'name'  => 'name',
            'label' => 'Name',
            'type'  => 'text',
        ],
        'email' => [
            'name'  => 'email',
            'type'  => 'email',
            'label' => 'Email',
        ],
        'password' => [
            'name'  => 'password',
            'type'  => 'password',
            'label' => 'Password',
        ],
    ];

    private $userInputHasOneRelation = [
        [
            'name' => 'accountDetails.nickname',
        ],
        [
            'name' => 'accountDetails.profile_picture',
        ],
    ];

    public function testUpdate()
    {
        $this->crudPanel->setModel(User::class);
        $this->crudPanel->addFields($this->userInputFields);
        $faker = Factory::create();
        $inputData = [
            'name'     => $faker->name,
            'email'    => $faker->safeEmail,
            'password' => bcrypt($faker->password()),
        ];

        $entry = $this->crudPanel->update(1, $inputData);

        $this->assertInstanceOf(User::class, $entry);
        $this->assertEntryEquals($inputData, $entry);
    }

    public function testUpdateExistingOneToOneRelationship()
    {
        $this->crudPanel->setModel(User::class);
        $this->crudPanel->addFields($this->userInputFields);
        $this->crudPanel->addFields($this->userInputHasOneRelation);
        $user = User::find(1)->load('accountDetails');
        $this->assertInstanceOf(AccountDetails::class, $user->accountDetails);
        $faker = Factory::create();
        $inputData = [
            'name'     => $user->name,
            'email'    => $user->email,
            'accountDetails' => [
                'profile_picture' => 'test_updated.jpg',
            ],
        ];
        $entry = $this->crudPanel->update(1, $inputData);

        $entry->load('accountDetails');

        $this->assertInstanceOf(AccountDetails::class, $entry->accountDetails);
        $this->assertEquals('test_updated.jpg', $entry->accountDetails->profile_picture);

    }

    public function testUpdateUnknownId()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->crudPanel->setModel(User::class);
        $this->crudPanel->addFields($this->userInputFields);
        $faker = Factory::create();
        $inputData = [
            'name'     => $faker->name,
            'email'    => $faker->safeEmail,
            'password' => bcrypt($faker->password()),
        ];

        $unknownId = DB::getPdo()->lastInsertId() + 2;
        $this->crudPanel->update($unknownId, $inputData);
    }

    public function testGetUpdateFields()
    {
        $this->crudPanel->setModel(User::class);
        $this->crudPanel->addFields($this->userInputFields);
        $faker = Factory::create();
        $inputData = [
            'name'     => $faker->name,
            'email'    => $faker->safeEmail,
            'password' => bcrypt($faker->password()),
        ];
        $entry = $this->crudPanel->create($inputData);
        $this->addValuesToExpectedFields($entry->id, $inputData);

        $updateFields = $this->crudPanel->getUpdateFields($entry->id);

        $this->assertEquals($this->expectedUpdatedFields, $updateFields);
    }

    public function testGetUpdateFieldsUnknownId()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->crudPanel->setModel(User::class);
        $this->crudPanel->addFields($this->userInputFields);

        $unknownId = DB::getPdo()->lastInsertId() + 2;
        $this->crudPanel->getUpdateFields($unknownId);
    }

    private function addValuesToExpectedFields($id, $inputData)
    {
        foreach ($inputData as $key => $value) {
            $this->expectedUpdatedFields[$key]['value'] = $value;
        }
        $this->expectedUpdatedFields['id']['value'] = $id;
    }
}
