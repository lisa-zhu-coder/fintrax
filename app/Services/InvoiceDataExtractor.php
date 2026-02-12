<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InvoiceDataExtractor
{
    /**
     * Extraer datos de una factura desde un archivo PDF o imagen
     */
    public function extractFromFile($filePath, $mimeType): array
    {
        $data = [
            'supplier_name' => '',
            'invoice_number' => '',
            'date' => null,
            'total_amount' => 0,
            'details' => null,
        ];

        try {
            if ($mimeType === 'application/pdf') {
                return $this->extractFromPdf($filePath);
            } elseif (in_array($mimeType, ['image/jpeg', 'image/jpg', 'image/png'])) {
                // Para imágenes, por ahora retornamos datos vacíos
                // Se podría implementar OCR con tesseract-ocr en el futuro
                return $data;
            }
        } catch (\Exception $e) {
            Log::warning('Error extrayendo datos de factura: ' . $e->getMessage());
        }

        return $data;
    }

    /**
     * Extraer datos de un archivo PDF
     */
    protected function extractFromPdf($filePath): array
    {
        $data = [
            'supplier_name' => '',
            'invoice_number' => '',
            'date' => null,
            'total_amount' => 0,
            'details' => null,
        ];

        try {
            // Verificar si la biblioteca está disponible
            if (!class_exists(\Smalot\PdfParser\Parser::class)) {
                Log::warning('La biblioteca smalot/pdfparser no está instalada. Ejecuta: composer require smalot/pdfparser');
                return $data;
            }

            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            // Normalizar el texto (reemplazar múltiples espacios y saltos de línea)
            $text = preg_replace('/\s+/', ' ', $text);
            $text = preg_replace('/\n+/', "\n", $text);

            // Extraer número de factura (buscar patrones comunes)
            $invoiceNumber = $this->extractInvoiceNumber($text);
            if ($invoiceNumber) {
                $data['invoice_number'] = $invoiceNumber;
            }

            // Extraer fecha (buscar patrones de fecha española)
            $date = $this->extractDate($text);
            if ($date) {
                $data['date'] = $date;
            }

            // Extraer proveedor (buscar en las primeras líneas)
            $supplier = $this->extractSupplierName($text);
            if ($supplier) {
                $data['supplier_name'] = $supplier;
            }

            // Extraer importe total (buscar "TOTAL", "TOTAL A PAGAR", "IMPORTE TOTAL", etc.)
            $totalAmount = $this->extractTotalAmount($text);
            if ($totalAmount > 0) {
                $data['total_amount'] = $totalAmount;
            }

            // NO guardar texto en details
            $data['details'] = null;

            // Log para debug
            Log::info('Datos extraídos de factura', [
                'invoice_number' => $data['invoice_number'],
                'supplier' => $data['supplier_name'],
                'date' => $data['date'],
                'total' => $data['total_amount'],
            ]);

        } catch (\Exception $e) {
            Log::error('Error procesando PDF: ' . $e->getMessage());
        }

        return $data;
    }

    /**
     * Extraer número de factura del texto
     */
    protected function extractInvoiceNumber(string $text): ?string
    {
        // Estrategia 1: Buscar patrones comunes en todo el texto
        $patterns = [
            // NÚMERO # 2025/944853727 o Número # 2025/944853727
            '/n[úu]mero\s*#?\s*([0-9]{4}\/[0-9]+)/i',
            // Factura INV/2025/07384 o Factura: INV/2025/07384
            '/factura\s+([A-Z]{2,}\/[0-9]{4}\/[0-9]+)/i',
            '/factura\s*[:\s]+([A-Z]{2,}\/[0-9]{4}\/[0-9]+)/i',
            // Formato YYYY/NNNNNNNNNN (como 2025/944853727)
            '/([0-9]{4}\/[0-9]{6,})/',
            // Factura: 12345, Factura Nº 12345, Factura número 12345
            '/factura\s*(?:n[º°]?|número|num)?\s*[:\s]*([A-Z0-9\-_\/]+)/i',
            // Nº Factura: 12345
            '/n[º°]?\s*factura\s*[:\s]*([A-Z0-9\-_\/]+)/i',
            // Invoice: INV-12345
            '/invoice\s*(?:n[º°]?|number|num)?\s*[:\s]*([A-Z0-9\-_\/]+)/i',
            // Ref: REF12345
            '/ref(?:erencia)?\s*[:\s]*([A-Z0-9\-_\/]+)/i',
            // Número: 12345
            '/número\s*[:\s]*([A-Z0-9\-_\/]+)/i',
            // Buscar números que aparecen después de palabras clave
            '/(?:factura|invoice|ref)\s+([A-Z0-9]{3,})/i',
            // Buscar formato INV/YYYY/NNNN directamente
            '/([A-Z]{2,}\/[0-9]{4}\/[0-9]+)/',
            // Número de documento: DOC-12345
            '/documento\s*(?:n[º°]?|número|num)?\s*[:\s]*([A-Z0-9\-_\/]+)/i',
            // Doc: DOC12345
            '/doc(?:umento)?\s*[:\s]*([A-Z0-9\-_\/]+)/i',
            // Número alfanumérico que sigue a "Nº" o "Número"
            '/n[º°]?\s*[:\s]*([A-Z0-9]{4,})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $number = trim($matches[1]);
                // Limpiar caracteres extraños pero mantener guiones y barras
                $number = preg_replace('/[^\w\-_\/]/', '', $number);
                if (strlen($number) >= 3 && strlen($number) <= 50) {
                    return $number;
                }
            }
        }

        // Estrategia 2: Buscar en las primeras líneas del documento (donde suele estar el número)
        $lines = explode("\n", $text);
        $firstLines = array_slice($lines, 0, 20);
        
        foreach ($firstLines as $line) {
            $line = trim($line);
            
            // Buscar patrones específicos en líneas individuales
            $linePatterns = [
                '/^[0-9]{4}\/[0-9]{6,}$/',           // 2025/944853727 (formato YYYY/NNNNNNNNNN)
                '/^[A-Z]{2,}\/[0-9]{4}\/[0-9]+$/i',  // INV/2025/07384
                '/^[A-Z]{2,}-[0-9]+$/i',              // INV-12345
                '/^[A-Z]{2,}[0-9]+$/i',               // INV12345
                '/^[0-9]{4,}$/',                      // 12345 (solo números largos)
            ];
            
            foreach ($linePatterns as $linePattern) {
                if (preg_match($linePattern, $line, $matches)) {
                    $number = trim($matches[0]);
                    if (strlen($number) >= 3 && strlen($number) <= 50) {
                        return $number;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Extraer fecha del texto
     */
    protected function extractDate(string $text): ?string
    {
        // Buscar fechas cerca de palabras clave primero
        $dateKeywords = ['fecha', 'date', 'emisión', 'emision', 'expedición', 'expedicion'];
        
        foreach ($dateKeywords as $keyword) {
            // Buscar hasta 50 caracteres después de la palabra clave
            if (preg_match('/' . preg_quote($keyword, '/') . '\s*[:\s]*([^\n]{0,50})/i', $text, $matches)) {
                $dateText = $matches[1];
                $date = $this->parseDateFromText($dateText);
                if ($date) {
                    return $date;
                }
            }
        }

        // Si no encontramos cerca de palabras clave, buscar en todo el texto
        // Patrones de fecha española: DD/MM/YYYY, DD-MM-YYYY, DD.MM.YYYY
        $patterns = [
            '/(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/',
            '/(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
                // Probar todas las fechas encontradas y tomar la más reciente válida
                foreach ($matches as $match) {
                    $date = $this->parseDateMatch($match);
                    if ($date) {
                        return $date;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Parsear una fecha desde un texto
     */
    protected function parseDateFromText(string $dateText): ?string
    {
        // Buscar patrones de fecha en el texto
        $patterns = [
            '/(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/',
            '/(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $dateText, $matches)) {
                $date = $this->parseDateMatch($matches);
                if ($date) {
                    return $date;
                }
            }
        }

        return null;
    }

    /**
     * Parsear un match de fecha
     */
    protected function parseDateMatch(array $matches): ?string
    {
        try {
            // Intentar parsear la fecha
            if (isset($matches[3]) && strlen($matches[3]) === 4) {
                // Formato DD/MM/YYYY
                $day = (int)$matches[1];
                $month = (int)$matches[2];
                $year = (int)$matches[3];
                
                // Validar que los valores sean razonables
                if ($day >= 1 && $day <= 31 && $month >= 1 && $month <= 12 && $year >= 2000 && $year <= 2100) {
                    $date = Carbon::createFromDate($year, $month, $day);
                    return $date->format('Y-m-d');
                }
            } elseif (isset($matches[1]) && strlen($matches[1]) === 4) {
                // Formato YYYY/MM/DD
                $year = (int)$matches[1];
                $month = (int)$matches[2];
                $day = (int)$matches[3];
                
                if ($day >= 1 && $day <= 31 && $month >= 1 && $month <= 12 && $year >= 2000 && $year <= 2100) {
                    $date = Carbon::createFromDate($year, $month, $day);
                    return $date->format('Y-m-d');
                }
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * Extraer nombre del proveedor del texto
     */
    protected function extractSupplierName(string $text): ?string
    {
        // Estrategia 1: Buscar específicamente "Sardinha de Artesanato" primero
        if (preg_match('/(Sardinha\s+de\s+Artesanato[^,\n]*(?:S\.?L\.?)?)/i', $text, $matches)) {
            $supplier = trim($matches[1]);
            $supplier = preg_replace('/\s+/', ' ', $supplier);
            $supplier = ucwords(strtolower($supplier));
            $supplier = preg_replace('/\s+s\.\s*l\./i', ' S.L.', $supplier);
            return $supplier;
        }

        // Estrategia 2: Buscar después de palabras clave comunes (expandidas)
        $supplierKeywords = [
            'proveedor', 'supplier', 'vendedor', 'vendor', 
            'empresa', 'company', 'cliente', 'client',
            'de:', 'from:', 'emite:', 'emite', 'emitido por', 'issued by',
            'razón social', 'denominación social', 'nombre comercial',
            'vendedor:', 'seller:', 'supplier:', 'proveedor:'
        ];

        foreach ($supplierKeywords as $keyword) {
            // Buscar hasta 120 caracteres después de la palabra clave
            if (preg_match('/' . preg_quote($keyword, '/') . '\s*[:\s]*([^\n]{5,120})/i', $text, $matches)) {
                $supplier = trim($matches[1]);
                $supplier = preg_replace('/\s+/', ' ', $supplier);
                // Eliminar números al final que puedan ser CIF, teléfono, etc.
                $supplier = preg_replace('/\s+\d+[A-Z]?$/', '', $supplier);
                // Eliminar información adicional después de comas o puntos
                $supplier = preg_replace('/[,;].*$/', '', $supplier);
                
                if (strlen($supplier) >= 3 && strlen($supplier) <= 200) {
                    return $supplier;
                }
            }
        }

        // Estrategia 3: Buscar en las primeras líneas del texto (donde suele estar el proveedor)
        $lines = explode("\n", $text);
        $firstLines = array_slice($lines, 0, 25); // Aumentado a 25 líneas

        // Buscar líneas que parezcan nombres de empresa
        foreach ($firstLines as $line) {
            $line = trim($line);
            
            // Saltar líneas muy cortas, números puros, fechas, emails, URLs
            if (strlen($line) < 5 || 
                preg_match('/^\d+$/', $line) || 
                preg_match('/^\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}$/', $line) ||
                preg_match('/@/', $line) ||
                preg_match('/^https?:\/\//', $line) ||
                preg_match('/^CIF|^NIF|^NIE|^CIF:/i', $line)) {
                continue;
            }

            // Buscar líneas que parezcan nombres de empresa
            // Deben tener al menos una letra mayúscula y contener letras
            // También acepta nombres que empiecen con letra seguida de número (como A1 PUBLICIDAD)
            if (preg_match('/[A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ0-9a-záéíóúñ\s\-]+(?:S\.?L\.?|S\.?A\.?|S\.?L\.?U\.?|S\.?C\.?O\.?O\.?P\.?|LTD|INC|CORP)?/u', $line) ||
                preg_match('/^[A-ZÁÉÍÓÚÑ][0-9]\s+[A-ZÁÉÍÓÚÑ]/u', $line)) {
                $supplier = trim($line);
                // Eliminar números al final (CIF, teléfono, etc.) pero mantener números en el nombre
                $supplier = preg_replace('/\s+\d{8,}[A-Z]?$/', '', $supplier); // Solo eliminar números largos (CIF)
                // Eliminar información adicional después de comas
                $supplier = preg_replace('/[,;].*$/', '', $supplier);
                $supplier = trim($supplier);
                
                if (strlen($supplier) >= 3 && strlen($supplier) <= 200) {
                    return $supplier;
                }
            }
        }

        // Estrategia 4: Buscar líneas con múltiples palabras en mayúsculas (típico de nombres de empresa)
        foreach ($firstLines as $line) {
            $line = trim($line);
            
            if (strlen($line) < 5 || strlen($line) > 200) {
                continue;
            }
            
            // Buscar líneas con al menos 2 palabras que empiecen con mayúscula
            // También acepta nombres que empiecen con letra seguida de número (como A1 PUBLICIDAD)
            if (preg_match('/^([A-ZÁÉÍÓÚÑ][0-9]?\s*[A-ZÁÉÍÓÚÑa-záéíóúñ0-9\s\-]+){2,}/u', $line)) {
                $supplier = trim($line);
                // Limpiar - solo eliminar números largos al final (CIF)
                $supplier = preg_replace('/\s+\d{8,}[A-Z]?$/', '', $supplier);
                $supplier = preg_replace('/[,;].*$/', '', $supplier);
                $supplier = trim($supplier);
                
                if (strlen($supplier) >= 3 && strlen($supplier) <= 200) {
                    return $supplier;
                }
            }
        }

        // Estrategia 5: Si no encontramos nada específico, tomar la primera línea sustancial
        foreach ($firstLines as $line) {
            $line = trim($line);
            // Debe tener al menos 5 caracteres y contener letras
            if (strlen($line) >= 5 && 
                preg_match('/[A-Za-zÁÉÍÓÚÑáéíóúñ]/u', $line) &&
                !preg_match('/^\d+[.,]?\d*$/', $line) &&
                !preg_match('/^\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}$/', $line) &&
                !preg_match('/@/', $line) &&
                !preg_match('/^https?:\/\//', $line)) {
                $supplier = preg_replace('/[,;].*$/', '', $line);
                return substr(trim($supplier), 0, 200);
            }
        }

        return null;
    }

    /**
     * Extraer importe total del texto
     */
    protected function extractTotalAmount(string $text): float
    {
        // Estrategia 1: Buscar patrones comunes para el total (mejorados para formato español)
        $patterns = [
            // Total: 346,67 € o Total 346,67 €
            '/total\s*[:\s]+([\d]{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)\s*€/i',
            '/total\s*[:\s]+€\s*([\d]{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)/i',
            // Total a pagar: 346,67 €
            '/total\s+a\s+pagar\s*[:\s]+([\d]{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)\s*€/i',
            // Importe total: 346,67 €
            '/importe\s+total\s*[:\s]+([\d]{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)\s*€/i',
            // Importe: 346,67 €
            '/importe\s*[:\s]+([\d]{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)\s*€/i',
            // Solo "Total" seguido de número
            '/total\s+([\d]{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)/i',
            // Amount: 346.67
            '/amount\s*[:\s]+([\d]{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)/i',
            // Suma total: 346,67
            '/suma\s+total\s*[:\s]+([\d]{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)/i',
            // Precio total: 346,67
            '/precio\s+total\s*[:\s]+([\d]{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $amount = $this->parseAmount($matches[1]);
                if ($amount > 0) {
                    return $amount;
                }
            }
        }

        // Estrategia 2: Buscar en las últimas líneas del documento (donde suele estar el total)
        $lines = explode("\n", $text);
        $lastLines = array_slice($lines, -15); // Últimas 15 líneas
        
        foreach ($lastLines as $line) {
            $line = trim($line);
            
            // Buscar líneas que contengan "total" o "importe" seguido de un número
            if (preg_match('/(?:total|importe|suma|precio)\s*[:\s]*([\d]{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)/i', $line, $matches)) {
                $amount = $this->parseAmount($matches[1]);
                if ($amount > 0) {
                    return $amount;
                }
            }
        }

        // Estrategia 3: Buscar números grandes que puedan ser el total (últimos números grandes encontrados)
        if (preg_match_all('/(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)\s*€/i', $text, $matches)) {
            $amounts = array_map([$this, 'parseAmount'], $matches[1]);
            if (!empty($amounts)) {
                // Retornar el mayor (probablemente el total)
                return max($amounts);
            }
        }

        // Estrategia 4: Buscar números grandes sin símbolo de euro en las últimas líneas
        foreach ($lastLines as $line) {
            $line = trim($line);
            // Buscar números que parezcan importes (con decimales o separadores de miles)
            if (preg_match('/(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)/', $line, $matches)) {
                $amount = $this->parseAmount($matches[1]);
                // Solo considerar si es un importe razonable (mayor a 1 y menor a 1 millón)
                if ($amount > 1 && $amount < 1000000) {
                    return $amount;
                }
            }
        }

        // Estrategia 5: Buscar el número más grande en todo el documento que parezca un importe
        if (preg_match_all('/(\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?)/', $text, $matches)) {
            $amounts = [];
            foreach ($matches[1] as $match) {
                $amount = $this->parseAmount($match);
                // Solo considerar importes razonables
                if ($amount > 1 && $amount < 1000000) {
                    $amounts[] = $amount;
                }
            }
            if (!empty($amounts)) {
                // Retornar el mayor
                return max($amounts);
            }
        }

        return 0;
    }

    /**
     * Convertir string de importe a float
     */
    protected function parseAmount(string $amount): float
    {
        // Limpiar el string
        $amount = trim($amount);
        
        // Formato español: 346,67 (coma como separador decimal)
        // Formato inglés: 346.67 (punto como separador decimal)
        
        // Si hay punto y coma, determinar cuál es el separador decimal
        if (strpos($amount, ',') !== false && strpos($amount, '.') !== false) {
            // Si hay ambos, determinar por posición
            $commaPos = strrpos($amount, ',');
            $dotPos = strrpos($amount, '.');
            
            // El que está más a la derecha es probablemente el separador decimal
            if ($commaPos > $dotPos) {
                // Coma es el separador decimal, punto es separador de miles
                $amount = str_replace('.', '', $amount);
                $amount = str_replace(',', '.', $amount);
            } else {
                // Punto es el separador decimal, coma es separador de miles
                $amount = str_replace(',', '', $amount);
            }
        } elseif (strpos($amount, ',') !== false) {
            // Solo hay coma
            // Si hay 3 dígitos después de la coma, probablemente es separador de miles
            if (preg_match('/,(\d{3})/', $amount)) {
                // Separador de miles, eliminar
                $amount = str_replace(',', '', $amount);
            } else {
                // Separador decimal, convertir a punto
                $amount = str_replace(',', '.', $amount);
            }
        }
        // Si solo hay punto, dejarlo como está (ya es formato correcto)

        return (float) $amount;
    }
}
