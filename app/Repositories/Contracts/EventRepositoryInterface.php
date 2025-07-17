<?php

namespace App\Repositories\Contracts;

use App\Models\Event;

interface EventRepositoryInterface
{
    // ========================================
    // EXISTING METHODS
    // ========================================
    
    public function create(array $data): Event;
    
    public function update(int $id, array $data): Event;
    
    public function delete(int $id): bool;
    
    public function findById(int $id): ?Event;
    
    public function findByIdAndHost(int $id, int $hostId): ?Event;
    
    public function getByHost(int $hostId, string $status = null): array;
    
    public function publishEvent(int $id): Event;
    
    public function cancelEvent(int $id, string $reason = null): Event;
    
    public function getUpcomingEvents(int $limit = 10): array;
    
    /**
     * Search events by filters (existing method)
     */
    public function searchEvents(array $filters): array;

    // ========================================
    // NEW METHODS FOR HOME SCREEN API
    // ========================================
    
    /**
     * Get events by venue category ID
     */
    public function getEventsByCategoryId(int $categoryId, int $limit = 10, int $offset = 0): array;
    
    /**
     * Get count of events for specific venue category ID
     */
    public function getEventsCategoryIdCount(int $categoryId): int;
    
    /**
     * Get count of events for category ID (alias for consistency)
     */
    public function getCategoryIdCount(int $categoryId): int;
    
    /**
     * Get filtered events based on multiple criteria
     */
    public function getFilteredEvents(array $filters, int $limit = 10, int $offset = 0): array;
    
    /**
     * Get count of filtered events
     */
    public function getFilteredEventsCount(array $filters): int;
    
    /**
     * Get hot/trending events with high attendance
     */
    public function getHotEvents(int $limit = 5): array;
    
    /**
     * Get recent events for a user
     */
    public function getRecentEvents(int $userId, int $limit = 10): array;
    
    /**
     * Search events by query string (new method with different name)
     */
    public function searchEventsByQuery(string $query, int $limit = 10, int $offset = 0): array;
    
    /**
     * Get count of search results
     */
    public function getSearchCount(string $query): int;
    
    /**
     * Check if user has joined an event
     */
    public function isUserJoinedEvent(int $eventId, int $userId): bool;
    
    /**
     * Get total count of published events
     */
    public function getTotalPublishedEventsCount(): int;
}