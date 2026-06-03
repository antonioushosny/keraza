<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class MultiUserEloquentProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials) ||
            (count($credentials) === 1 &&
             array_key_exists('password', $credentials))) {
            return null;
        }

        // First we will dynamic query to find any matching users
        $query = $this->createModel()->newQuery();

        foreach ($credentials as $key => $value) {
            if (str_contains($key, 'password')) {
                continue;
            }

            if (is_array($value) || $value instanceof \Illuminate\Contracts\Support\Arrayable) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        // Get all matching users (e.g. users sharing this phone number)
        $users = $query->get();

        if ($users->isEmpty()) {
            return null;
        }

        // If password is set in the credentials, find the user whose password matches
        if (isset($credentials['password'])) {
            foreach ($users as $user) {
                if ($this->hasher->check($credentials['password'], $user->getAuthPassword())) {
                    return $user;
                }
            }
        }

        // If no password check succeeded or was provided, fallback to the first matched user
        return $users->first();
    }
}
