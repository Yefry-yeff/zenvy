<?php

if (!function_exists('hondurasNow')) {
    /**
     * Obtener la fecha y hora actual en zona horaria de Honduras
     */
    function hondurasNow($format = null)
    {
        $carbon = \Illuminate\Support\Carbon::now('America/Tegucigalpa');
        
        return $format ? $carbon->format($format) : $carbon;
    }
}

if (!function_exists('hondurasDate')) {
    /**
     * Convertir una fecha a zona horaria de Honduras
     */
    function hondurasDate($date, $format = null)
    {
        if (!$date) return null;
        
        $carbon = \Illuminate\Support\Carbon::parse($date)->setTimezone('America/Tegucigalpa');
        
        return $format ? $carbon->format($format) : $carbon;
    }
}

if (!function_exists('hondurasTimestamp')) {
    /**
     * Obtener timestamp actual en zona horaria de Honduras
     */
    function hondurasTimestamp()
    {
        return \Illuminate\Support\Carbon::now('America/Tegucigalpa')->timestamp;
    }
}

if (!function_exists('formatDateHonduras')) {
    /**
     * Formatear fecha en español para Honduras
     */
    function formatDateHonduras($date, $includeTime = false)
    {
        if (!$date) return '';
        
        $carbon = \Illuminate\Support\Carbon::parse($date)->setTimezone('America/Tegucigalpa');
        $carbon->setLocale('es');
        
        $format = $includeTime ? 'd/m/Y H:i:s' : 'd/m/Y';
        
        return $carbon->format($format);
    }
}

if (!function_exists('formatDateTimeHonduras')) {
    /**
     * Formatear fecha y hora completa para Honduras
     */
    function formatDateTimeHonduras($date)
    {
        if (!$date) return '';
        
        $carbon = \Illuminate\Support\Carbon::parse($date)->setTimezone('America/Tegucigalpa');
        $carbon->setLocale('es');
        
        return $carbon->format('d/m/Y H:i:s');
    }
}