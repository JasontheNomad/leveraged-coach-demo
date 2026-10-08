<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DailyService
{
    private string $baseUrl = 'https://api.daily.co/v1';

    private function client()
    {
        return Http::withToken(config('services.daily.api_key'))
            ->acceptJson();
    }

    public function createRoom(string $name, bool $enableRecording = true): ?array
    {
        try {
            $properties = [
                'enable_chat'        => true,
                'enable_screenshare' => true,
                'enable_prejoin_ui'  => true,
            ];

            if ($enableRecording) {
                $properties['enable_recording'] = 'cloud';
            }

            $response = $this->client()->post("{$this->baseUrl}/rooms", [
                'name'       => $name,
                'properties' => $properties,
            ]);

            Log::info('Daily.co createRoom response', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);

            if ($response->failed()) {
                Log::error('Daily.co createRoom failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Daily.co createRoom exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function updateRoom(string $name, array $properties): ?array
    {
        try {
            $response = $this->client()->post("{$this->baseUrl}/rooms/{$name}", [
                'properties' => $properties,
            ]);

            Log::info('Daily.co updateRoom response', [
                'room_name' => $name,
                'status'    => $response->status(),
                'body'      => $response->json(),
            ]);

            if ($response->failed()) {
                Log::error('Daily.co updateRoom failed', [
                    'room_name' => $name,
                    'status'    => $response->status(),
                    'body'      => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Daily.co updateRoom exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function getRecordings(string $roomName): array
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/recordings", [
                'room_name' => $roomName,
            ]);

            if ($response->failed()) {
                Log::error('Daily.co getRecordings failed', [
                    'room_name' => $roomName,
                    'status'    => $response->status(),
                    'body'      => $response->body(),
                ]);
                return [];
            }

            return $response->json('data', []);
        } catch (\Throwable $e) {
            Log::error('Daily.co getRecordings exception', ['message' => $e->getMessage()]);
            return [];
        }
    }

    public function getRecordingDownloadLink(string $recordingId): ?string
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/recordings/{$recordingId}/access-link");

            if ($response->failed()) {
                Log::error('Daily.co getRecordingDownloadLink failed', [
                    'recording_id' => $recordingId,
                    'status'       => $response->status(),
                    'body'         => $response->body(),
                ]);
                return null;
            }

            return $response->json('download_link');
        } catch (\Throwable $e) {
            Log::error('Daily.co getRecordingDownloadLink exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function deleteRecording(string $recordingId): bool
    {
        try {
            $response = $this->client()->delete("{$this->baseUrl}/recordings/{$recordingId}");

            if ($response->successful()) {
                Log::info('Daily.co recording deleted', ['recording_id' => $recordingId]);
                return true;
            }

            Log::error('Daily.co deleteRecording failed', [
                'recording_id' => $recordingId,
                'status'       => $response->status(),
                'body'         => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Daily.co deleteRecording exception', [
                'recording_id' => $recordingId,
                'message'      => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getRoom(string $roomName): ?array
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/rooms/{$roomName}");

            if ($response->failed()) {
                Log::error('Daily.co getRoom failed', [
                    'room_name' => $roomName,
                    'status'    => $response->status(),
                    'body'      => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('Daily.co getRoom exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function getMeetingToken(string $roomName, bool $isOwner = false): ?string
    {
        try {
            $response = $this->client()->post("{$this->baseUrl}/meeting-tokens", [
                'properties' => [
                    'room_name' => $roomName,
                    'is_owner' => $isOwner,
                ],
            ]);

            if ($response->failed()) {
                Log::error('Daily.co getMeetingToken failed', [
                    'room_name' => $roomName,
                    'status'    => $response->status(),
                    'body'      => $response->body(),
                ]);
                return null;
            }

            return $response->json('token');
        } catch (\Throwable $e) {
            Log::error('Daily.co getMeetingToken exception', ['message' => $e->getMessage()]);
            return null;
        }
    }
}
