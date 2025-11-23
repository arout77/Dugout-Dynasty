<?php

namespace Core;

class Pagination
{
    protected int $totalItems;
    protected int $itemsPerPage;
    protected int $currentPage;
    protected int $totalPages;

    /**
     * @param int $totalItems
     * @param int $itemsPerPage
     * @param int $currentPage
     */
    public function __construct( int $totalItems, int $itemsPerPage, int $currentPage = 1 )
    {
        $this->totalItems   = $totalItems;
        $this->itemsPerPage = $itemsPerPage;
        $this->totalPages   = (int) ceil( $this->totalItems / $this->itemsPerPage );
        $this->currentPage  = $this->setCurrentPage( $currentPage );
    }

    /**
     * @param int $page
     * @return mixed
     */
    private function setCurrentPage( int $page ): int
    {
        if ( $page < 1 )
        {
            return 1;
        }
        if ( $page > $this->totalPages && $this->totalPages > 0 )
        {
            return $this->totalPages;
        }
        return $page;
    }

    /**
     * @return mixed
     */
    public function getLimit(): int
    {
        return $this->itemsPerPage;
    }

    public function getOffset(): int
    {
        return ( $this->currentPage - 1 ) * $this->itemsPerPage;
    }

    public function render(): string
    {
        if ( $this->totalPages <= 1 )
        {
            return '';
        }

        $queryParams = $_GET;
        unset( $queryParams['page'] );
        $queryString = http_build_query( $queryParams );
        $queryString = $queryString ? "&{$queryString}" : "";

        $output = '<nav aria-label="Page navigation"><ul class="flex items-center space-x-2">';

        $linkClasses     = 'px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600';
        $disabledClasses = 'px-4 py-2 bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md text-gray-400 dark:text-gray-500 cursor-not-allowed';
        $activeClasses   = 'px-4 py-2 bg-blue-500 dark:bg-blue-600 text-white border border-blue-500 dark:border-blue-600 rounded-md';

        // Previous button
        if ( $this->currentPage > 1 )
        {
            $prevPage = $this->currentPage - 1;
            $output .= "<li><a href='?page={$prevPage}{$queryString}' class='{$linkClasses}'>Previous</a></li>";
        }
        else
        {
            $output .= "<li><span class='{$disabledClasses}'>Previous</span></li>";
        }

        // Page number links
        for ( $i = 1; $i <= $this->totalPages; $i++ )
        {
            if ( $i === $this->currentPage )
            {
                $output .= "<li><span class='{$activeClasses}'>{$i}</span></li>";
            }
            else
            {
                $output .= "<li><a href='?page={$i}{$queryString}' class='{$linkClasses}'>{$i}</a></li>";
            }
        }

        // Next button
        if ( $this->currentPage < $this->totalPages )
        {
            $nextPage = $this->currentPage + 1;
            $output .= "<li><a href='?page={$nextPage}{$queryString}' class='{$linkClasses}'>Next</a></li>";
        }
        else
        {
            $output .= "<li><span class='{$disabledClasses}'>Next</span></li>";
        }

        $output .= '</ul></nav>';
        return $output;
    }
}
