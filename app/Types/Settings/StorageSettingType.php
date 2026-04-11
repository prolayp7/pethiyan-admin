<?php

namespace App\Types\Settings;

use App\Interfaces\SettingInterface;
use App\Traits\SettingTrait;

class StorageSettingType implements SettingInterface
{
    use SettingTrait;

    public string $storageDriver  = 'local'; // 'local' | 's3'

    // AWS S3 fields (only required when driver = s3)
    public string $awsAccessKeyId     = '';
    public string $awsSecretAccessKey = '';
    public string $awsRegion          = '';
    public string $awsBucket          = '';
    public string $awsAssetUrl        = '';

    protected static function getValidationRules(): array
    {
        $driver = request('storageDriver', 'local');

        $rules = [
            'storageDriver' => 'required|in:local,s3',
        ];

        if ($driver === 's3') {
            $rules = array_merge($rules, [
                'awsAccessKeyId'     => 'required',
                'awsSecretAccessKey' => 'required',
                'awsRegion'          => 'required',
                'awsBucket'          => 'required',
                'awsAssetUrl'        => 'required|url',
            ]);
        }

        return $rules;
    }
}
