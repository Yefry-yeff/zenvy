-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 11-08-2025 a las 01:10:11
-- Versión del servidor: 5.7.33
-- Versión de PHP: 8.2.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `db_zenvy`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE  PROCEDURE `sp_crud_producto` (IN `p_accion` INT, IN `p_id` INT, IN `p_nombre` VARCHAR(80), IN `p_descripcion` VARCHAR(45), IN `p_isv_id` INT, IN `p_precio_base` DOUBLE, IN `p_ultimo_costo_compra` DOUBLE, IN `p_costo_promedio` DOUBLE, IN `p_codigo_barra` VARCHAR(100), IN `p_codigo_estatal` VARCHAR(45), IN `p_estado_id` INT, IN `p_subcategoria_id` INT, IN `p_marca_id` INT, IN `p_unidad_medida_venta_id` INT, IN `p_precio1` DECIMAL(16,2), IN `p_precio2` DECIMAL(16,2), IN `p_precio3` DECIMAL(16,2), IN `p_precio4` DECIMAL(16,2), IN `p_users_id` BIGINT, IN `p_descuento_unitario` DECIMAL(16,2), IN `p_descuento_tercera` TINYINT, IN `p_descuento_cuarta` TINYINT)   BEGIN
    -- Normalizar banderas a 0/1
    DECLARE v_desc_tercera TINYINT(1);
    DECLARE v_desc_cuarta  TINYINT(1);

    SET v_desc_tercera = IFNULL(p_descuento_tercera, 0);
    SET v_desc_cuarta  = IFNULL(p_descuento_cuarta , 0);

    SET v_desc_tercera = IF(v_desc_tercera IN (0,1), v_desc_tercera, 0);
    SET v_desc_cuarta  = IF(v_desc_cuarta  IN (0,1), v_desc_cuarta , 0);

    IF p_accion = 1 THEN
        -- INSERTAR
        INSERT INTO producto (
            nombre,
            isv_id,
            descripcion,
            descuento_unitario,
            descuento_tercera,
            descuento_cuarta,
            precio_base,
            ultimo_costo_compra,
            costo_promedio,
            codigo_barra,
            codigo_estatal,
            estado_id,
            subcategoria_id,
            marca_id,
            unidad_medida_venta_id,
            precio1, precio2, precio3, precio4,
            users_id, created_at, updated_at
        ) VALUES (
            p_nombre,
            p_isv_id,
            p_descripcion,
            p_descuento_unitario,
            v_desc_tercera,
            v_desc_cuarta,
            p_precio_base,
            p_ultimo_costo_compra,
            p_costo_promedio,
            p_codigo_barra,
            p_codigo_estatal,
            p_estado_id,
            p_subcategoria_id,
            p_marca_id,
            p_unidad_medida_venta_id,
            p_precio1, p_precio2, p_precio3, p_precio4,
            p_users_id, NOW(), NOW()
        );

    ELSEIF p_accion = 2 THEN
        -- ACTUALIZAR
        UPDATE producto
        SET
            nombre                   = p_nombre,
            isv_id                   = p_isv_id,
            descripcion              = p_descripcion,
            descuento_unitario       = p_descuento_unitario,
            descuento_tercera        = v_desc_tercera,
            descuento_cuarta         = v_desc_cuarta,
            precio_base              = p_precio_base,
            ultimo_costo_compra      = p_ultimo_costo_compra,
            costo_promedio           = p_costo_promedio,
            codigo_barra             = p_codigo_barra,
            codigo_estatal           = p_codigo_estatal,
            estado_id                = p_estado_id,
            subcategoria_id          = p_subcategoria_id,
            marca_id                 = p_marca_id,
            unidad_medida_venta_id   = p_unidad_medida_venta_id,
            precio1                  = p_precio1,
            precio2                  = p_precio2,
            precio3                  = p_precio3,
            precio4                  = p_precio4,
            users_id                 = p_users_id,
            updated_at               = NOW()
        WHERE id = p_id;

    ELSEIF p_accion = 3 THEN
        -- ELIMINAR
        DELETE FROM producto WHERE id = p_id;

    ELSEIF p_accion = 4 THEN
        -- CONSULTAR DETALLADO
        SELECT 
            p.nombre                        AS 'Nombre',
            p.descripcion                   AS 'Descripcion',
            i.cantidad                      AS 'ISV',           -- % o valor definido en tabla isv
            p.precio_base                   AS 'PrecioBase',
            p.codigo_barra                  AS 'Codigo de Barras',
            p.codigo_estatal                AS 'Codigo Estatal',
            p.descuento_unitario            AS 'Descuento Unitario',
            p.descuento_tercera             AS 'Aplica Tercera Edad', -- 0/1
            p.descuento_cuarta              AS 'Aplica Cuarta Edad',  -- 0/1
            m.nombre                        AS 'Marca',
            c.nombre                        AS 'Categoria',
            sc.nombre                       AS 'Subcategoria',
            uc.unidad                       AS 'Unidad',
            uc.nombre                       AS 'Presentacion',
            p.precio1, p.precio2, p.precio3, p.precio4
        FROM producto p
        INNER JOIN subcategoria sc   ON sc.id = p.subcategoria_id
        INNER JOIN categoria c       ON c.id = sc.categoria_id
        INNER JOIN marca m           ON m.id = p.marca_id
        INNER JOIN unidad_medida uc  ON uc.id = p.unidad_medida_venta_id
        INNER JOIN isv i             ON i.id = p.isv_id
        WHERE p.id = p_id;
    END IF;
END$$

CREATE  PROCEDURE `sp_gestion_menu_sidebar` (IN `accion` INT, IN `p_id` INT, IN `p_menu_nombre` VARCHAR(100), IN `p_icono` VARCHAR(191) CHARSET utf8mb4, IN `p_submenu` VARCHAR(100), IN `p_orden` INT, IN `p_estado_id` INT)   BEGIN
    DECLARE v_menu_grupo_id INT;
    DECLARE v_route VARCHAR(255);

    IF accion = 1 THEN
        -- CREACIÓN

        -- Verificar si el grupo ya existe
        SELECT id INTO v_menu_grupo_id FROM menu_grupo WHERE nombre = p_menu_nombre LIMIT 1;

        IF v_menu_grupo_id IS NULL THEN
            INSERT INTO menu_grupo (nombre, icon, created_at)
            VALUES (p_menu_nombre, p_icono, NOW());

            SET v_menu_grupo_id = LAST_INSERT_ID();
        END IF;

        -- Generar la ruta (grupo.submenu)
SET v_route = CONCAT(fn_clean_studly(p_menu_nombre), '.', fn_clean_studly(p_submenu));

        -- Insertar el submenú en `menu`
        INSERT INTO menu (
            txt_comentario, parent_id, route, orden, estado_id, created_at
        ) VALUES (
            p_submenu, v_menu_grupo_id, v_route, p_orden, p_estado_id, NOW()
        );

    ELSEIF accion = 2 THEN
        -- ACTUALIZAR

        -- Obtener el ID del grupo
        SELECT id INTO v_menu_grupo_id FROM menu_grupo WHERE nombre = p_menu_nombre LIMIT 1;

        -- Actualizar menú_grupo
        UPDATE menu_grupo
        SET icon = p_icono,
            updated_at = NOW()
        WHERE id = v_menu_grupo_id;

        -- Generar la ruta actualizada
        SET v_route = CONCAT(LOWER(REPLACE(p_menu_nombre, ' ', '-')), '.', LOWER(REPLACE(p_submenu, ' ', '-')));

        -- Actualizar menú
        UPDATE menu
        SET
            txt_comentario = p_submenu,
            parent_id = v_menu_grupo_id,
            route = v_route,
            orden = p_orden,
            estado_id = p_estado_id,
            updated_at = NOW()
        WHERE id = p_id;

    ELSEIF accion = 3 THEN
        -- CONSULTAR TODO
        SELECT 
        	m.id,
            mg.nombre AS menu,
            m.txt_comentario ,
            mg.icon ,
            m.orden ,
            m.estado_id
        FROM menu m
        INNER JOIN menu_grupo mg ON mg.id = m.parent_id
        ORDER BY mg.id, m.orden;

    END IF;
END$$

--
-- Funciones
--
CREATE  FUNCTION `fn_clean_studly` (`input` VARCHAR(255)) RETURNS VARCHAR(255) CHARSET latin1 DETERMINISTIC BEGIN
  DECLARE cleaned VARCHAR(255);
  
  -- Reemplazar acentos manualmente
  SET cleaned = input;
  SET cleaned = REPLACE(cleaned, 'á', 'a');
  SET cleaned = REPLACE(cleaned, 'é', 'e');
  SET cleaned = REPLACE(cleaned, 'í', 'i');
  SET cleaned = REPLACE(cleaned, 'ó', 'o');
  SET cleaned = REPLACE(cleaned, 'ú', 'u');
  SET cleaned = REPLACE(cleaned, 'Á', 'A');
  SET cleaned = REPLACE(cleaned, 'É', 'E');
  SET cleaned = REPLACE(cleaned, 'Í', 'I');
  SET cleaned = REPLACE(cleaned, 'Ó', 'O');
  SET cleaned = REPLACE(cleaned, 'Ú', 'U');
  SET cleaned = REPLACE(cleaned, 'ñ', 'n');
  SET cleaned = REPLACE(cleaned, 'Ñ', 'N');

  -- Elimina algunos caracteres comunes (puedes ampliar esta lista)
SET cleaned = REPLACE(cleaned, '-', '');
SET cleaned = REPLACE(cleaned, '_', '');
SET cleaned = REPLACE(cleaned, '.', '');
SET cleaned = REPLACE(cleaned, ',', '');
SET cleaned = REPLACE(cleaned, ';', '');
SET cleaned = REPLACE(cleaned, ':', '');
SET cleaned = REPLACE(cleaned, '?', '');
SET cleaned = REPLACE(cleaned, '¿', '');
SET cleaned = REPLACE(cleaned, '!', '');
SET cleaned = REPLACE(cleaned, '¡', '');
SET cleaned = REPLACE(cleaned, '/', '');
SET cleaned = REPLACE(cleaned, '(', '');
SET cleaned = REPLACE(cleaned, ')', '');
SET cleaned = REPLACE(cleaned, '[', '');
SET cleaned = REPLACE(cleaned, ']', '');
SET cleaned = REPLACE(cleaned, '{', '');
SET cleaned = REPLACE(cleaned, '}', '');
SET cleaned = REPLACE(cleaned, '@', '');
SET cleaned = REPLACE(cleaned, '#', '');
SET cleaned = REPLACE(cleaned, '$', '');
SET cleaned = REPLACE(cleaned, '%', '');
SET cleaned = REPLACE(cleaned, '^', '');
SET cleaned = REPLACE(cleaned, '&', '');
SET cleaned = REPLACE(cleaned, '*', '');
SET cleaned = REPLACE(cleaned, '=', '');
SET cleaned = REPLACE(cleaned, '+', '');
SET cleaned = REPLACE(cleaned, '"', '');
SET cleaned = REPLACE(cleaned, "'", '');
SET cleaned = REPLACE(cleaned, '`', '');
SET cleaned = REPLACE(cleaned, '~', '');
SET cleaned = REPLACE(cleaned, '|', '');
SET cleaned = REPLACE(cleaned, '<', '');
SET cleaned = REPLACE(cleaned, '>', '');

  -- Convertir a minúsculas para procesamiento
  SET cleaned = LOWER(cleaned);

  -- Convertir a StudlyCase manualmente
  SET cleaned = CONCAT_WS('', 
    UPPER(SUBSTRING(cleaned, 1, 1)),
    SUBSTRING(cleaned, 2)
  );

  -- Remover espacios y capitalizar cada palabra
  WHILE LOCATE(' ', cleaned) > 0 DO
    SET cleaned = INSERT(
      cleaned,
      LOCATE(' ', cleaned),
      2,
      UPPER(SUBSTRING(cleaned, LOCATE(' ', cleaned) + 1, 1))
    );
  END WHILE;

  RETURN REPLACE(cleaned, ' ', '');
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bodega`
--

CREATE TABLE `bodega` (
  `id` int(11) NOT NULL,
  `nombre` varchar(45) DEFAULT NULL,
  `principal` tinyint(4) DEFAULT NULL,
  `direccion_id` int(11) NOT NULL,
  `estado_id` int(11) NOT NULL,
  `tienda_id` int(11) NOT NULL,
  `created_at` varchar(45) DEFAULT NULL,
  `updated_at` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel_cache356a192b7913b04c54574d18c28d46e6395428ab', 'i:1;', 1754864764),
('laravel_cache356a192b7913b04c54574d18c28d46e6395428ab:timer', 'i:1754864764;', 1754864764),
('laravel_cachebodega@zenvy.com|127.0.0.1', 'i:2;', 1754861508),
('laravel_cachebodega@zenvy.com|127.0.0.1:timer', 'i:1754861508;', 1754861508),
('laravel_cachebodega1@zenvy.com|127.0.0.1', 'i:1;', 1754861523),
('laravel_cachebodega1@zenvy.com|127.0.0.1:timer', 'i:1754861523;', 1754861523),
('laravel_cachesupervisor1@zenvy.com|127.0.0.1', 'i:1;', 1754861325),
('laravel_cachesupervisor1@zenvy.com|127.0.0.1:timer', 'i:1754861325;', 1754861325);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cai`
--

CREATE TABLE `cai` (
  `id` int(11) NOT NULL,
  `cai` varchar(60) DEFAULT NULL,
  `fecha_limite_emision` date DEFAULT NULL,
  `fecha_solicitud` date DEFAULT NULL,
  `punto_emision` varchar(100) DEFAULT NULL,
  `tipo_documento_fiscal_id` int(11) NOT NULL,
  `cantidad_solicitada` int(11) DEFAULT NULL,
  `cantidad_otorgada` int(11) DEFAULT NULL,
  `rango_inicio` varchar(45) DEFAULT NULL,
  `rango_final` varchar(45) DEFAULT NULL,
  `tienda_id` int(11) NOT NULL,
  `users_registro_id` bigint(20) UNSIGNED NOT NULL,
  `estado_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `caja`
--

CREATE TABLE `caja` (
  `id` int(11) NOT NULL,
  `tienda_id` int(11) NOT NULL,
  `users_id` bigint(20) UNSIGNED NOT NULL,
  `balance` decimal(16,2) DEFAULT NULL,
  `fecha_apertura` timestamp NULL DEFAULT NULL,
  `fecha_cierre` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `estado_caja` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `id` int(11) NOT NULL,
  `nombre` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cierre_de_caja`
--

CREATE TABLE `cierre_de_caja` (
  `id` int(11) NOT NULL,
  `caja_id` int(11) NOT NULL,
  `total_efectivo` decimal(16,2) DEFAULT NULL,
  `total_tarjeta` decimal(16,2) DEFAULT NULL,
  `total_cheque` decimal(16,2) DEFAULT NULL,
  `conteo_efectivo` decimal(16,2) DEFAULT NULL,
  `conteo_tarjeta` decimal(16,2) DEFAULT NULL,
  `conteo_cheque` decimal(16,2) DEFAULT NULL,
  `diferencia_efectivo` decimal(16,2) DEFAULT NULL,
  `diferencia_cheque` decimal(16,2) DEFAULT NULL,
  `diferencia_tarjeta` decimal(16,2) DEFAULT NULL,
  `1` int(11) DEFAULT NULL,
  `2` int(11) DEFAULT NULL,
  `5` int(11) DEFAULT NULL,
  `10` int(11) DEFAULT NULL,
  `20` int(11) DEFAULT NULL,
  `50` int(11) DEFAULT NULL,
  `100` int(11) DEFAULT NULL,
  `200` int(11) DEFAULT NULL,
  `500` int(11) DEFAULT NULL,
  `001` int(11) DEFAULT NULL,
  `002` int(11) DEFAULT NULL,
  `005` int(11) DEFAULT NULL,
  `010` int(11) DEFAULT NULL,
  `020` int(11) DEFAULT NULL,
  `050` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) DEFAULT NULL,
  `correo` varchar(45) DEFAULT NULL,
  `direccion_id` int(11) NOT NULL,
  `estado_id` int(11) NOT NULL,
  `identidad` varchar(45) DEFAULT NULL,
  `rtn` varchar(45) DEFAULT NULL,
  `telefono` varchar(45) DEFAULT NULL,
  `tipo_persona_id` int(11) NOT NULL,
  `tipo_cliente_id` int(11) NOT NULL,
  `users_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compra`
--

CREATE TABLE `compra` (
  `id` int(11) NOT NULL,
  `numero_factura` varchar(90) DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `fecha_emision` date DEFAULT NULL,
  `fecha_recepcion` date DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `updated_at` date DEFAULT NULL,
  `estado_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compra_has_producto`
--

CREATE TABLE `compra_has_producto` (
  `id` int(11) NOT NULL,
  `precio` decimal(60,2) NOT NULL,
  `cantidad_ingresada` int(11) NOT NULL,
  `cantidad_sin_asignar` int(11) DEFAULT NULL,
  `fecha_expiracion` date DEFAULT NULL,
  `sub_total_producto` decimal(60,2) DEFAULT NULL,
  `isv` decimal(60,2) DEFAULT NULL,
  `precio_total` decimal(60,2) DEFAULT NULL,
  `compra_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `unidad_compra_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamento`
--

CREATE TABLE `departamento` (
  `id` int(11) NOT NULL,
  `nombre` varchar(45) DEFAULT NULL,
  `user_registro_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `departamento`
--

INSERT INTO `departamento` (`id`, `nombre`, `user_registro_id`, `created_at`, `updated_at`) VALUES
(1, 'Atlántida', 14, '2025-08-03 03:28:06', '2025-08-03 03:28:06'),
(2, 'Choluteca', 14, '2025-08-03 03:28:06', '2025-08-03 03:28:06'),
(3, 'Colon', 14, '2025-08-03 03:28:06', '2025-08-05 18:07:25'),
(4, 'Comayagua', 14, '2025-08-03 03:28:06', '2025-08-03 03:28:06'),
(5, 'Copán', 14, '2025-08-03 03:28:06', '2025-08-03 03:28:06'),
(6, 'Cortés', 14, '2025-08-03 03:28:06', '2025-08-03 03:28:06'),
(7, 'El Paraíso', 14, '2025-08-03 03:28:06', '2025-08-03 03:28:06'),
(8, 'Francisco Morazán', 14, '2025-08-03 03:28:06', '2025-08-03 03:28:06'),
(9, 'Gracias a Dios', 14, '2025-08-03 03:28:06', '2025-08-03 03:28:06'),
(10, 'Intibucá', 14, '2025-08-03 03:28:06', '2025-08-03 03:28:06'),
(11, 'Islas de la Bahía', 14, '2025-08-03 03:28:06', '2025-08-03 03:28:06'),
(12, 'La Paz', 14, '2025-08-03 03:28:06', '2025-08-03 03:28:06'),
(13, 'Lempira', 14, '2025-08-03 03:28:06', '2025-08-03 03:28:06'),
(14, 'Ocotepeque', 14, '2025-08-03 03:28:06', '2025-08-03 03:28:06'),
(15, 'Olancho', 14, '2025-08-03 03:28:06', '2025-08-03 03:28:06'),
(16, 'Santa Bárbara', 14, '2025-08-03 03:28:06', '2025-08-03 03:28:06'),
(17, 'Valle', 14, '2025-08-03 03:28:06', '2025-08-03 03:28:06'),
(18, 'Yoro', 14, '2025-08-03 03:28:06', '2025-08-03 03:28:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `descuentos`
--

CREATE TABLE `descuentos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `monto` decimal(16,2) DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_fin` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `update_at` timestamp NULL DEFAULT NULL,
  `users_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `descuento_adulto`
--

CREATE TABLE `descuento_adulto` (
  `id` int(11) NOT NULL,
  `factura_id` int(11) NOT NULL,
  `dni` varchar(60) DEFAULT NULL,
  `nombre` varchar(70) DEFAULT NULL,
  `edad` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_factura_lote`
--

CREATE TABLE `detalle_factura_lote` (
  `id` int(11) NOT NULL,
  `factura_id` int(11) NOT NULL,
  `recibido_bodega_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad_usada` int(11) DEFAULT NULL,
  `precio_unitario` decimal(60,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `direccion`
--

CREATE TABLE `direccion` (
  `id` int(11) NOT NULL,
  `domicilio_tributario` varchar(100) DEFAULT NULL,
  `colonia` varchar(100) DEFAULT NULL,
  `calle_blv` varchar(100) DEFAULT NULL,
  `sector_zona` varchar(100) DEFAULT NULL,
  `bloque` varchar(100) DEFAULT NULL,
  `tipo_direccion_id` int(11) NOT NULL,
  `municipio_id` int(11) NOT NULL,
  `estado_id` int(11) NOT NULL,
  `latitud` varchar(45) DEFAULT NULL,
  `longitud` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `direccion`
--

INSERT INTO `direccion` (`id`, `domicilio_tributario`, `colonia`, `calle_blv`, `sector_zona`, `bloque`, `tipo_direccion_id`, `municipio_id`, `estado_id`, `latitud`, `longitud`, `created_at`, `updated_at`) VALUES
(5, 'Plaza San Martín', 'Colonia Miraflores', 'Blv. Fuerzas Armadas', 'Zona 8', 'Bloque E', 3, 35, 1, '14.0800', '-87.1900', '2025-08-03 03:32:23', '2025-08-05 21:16:11'),
(6, 'Plaza San Martín', '', '', '', '', 3, 38, 1, '', '', '2025-08-05 20:57:05', '2025-08-05 20:57:05'),
(7, 'Centro', '', '', '', '', 3, 38, 1, '', '', '2025-08-05 21:27:51', '2025-08-05 21:27:51'),
(8, NULL, 'X', 'X', 'X', 'X', 1, 36, 1, '', '', '2025-08-08 02:33:26', '2025-08-08 02:33:26'),
(9, 'Residencial Las Uvas', 'El rodeo', '', '2', '5', 3, 38, 1, '', '', '2025-08-09 23:24:24', '2025-08-09 23:24:24'),
(10, 'Centro al par de la alcaldia municipal', 'Centro', 'miguel cervantes', '', '2', 3, 36, 1, '', '', '2025-08-09 23:29:36', '2025-08-09 23:29:36'),
(11, 'Centro al par de la alcaldia municipal', 'Centro', 'miguel cervantes', '', '2', 3, 36, 1, '', '', '2025-08-09 23:30:53', '2025-08-09 23:30:53'),
(12, 'Cerro Grande Z4', '', '', '', '', 3, 36, 1, '', '', '2025-08-10 21:20:18', '2025-08-10 21:20:18');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `distribucion_stock`
--

CREATE TABLE `distribucion_stock` (
  `id` int(11) NOT NULL,
  `recibido_bodega_id` int(11) NOT NULL,
  `users_id` bigint(20) UNSIGNED NOT NULL,
  `cantidad_distribuida` int(11) DEFAULT NULL,
  `cantidad_disponible_en_seccion` int(11) DEFAULT '0',
  `precio_unitario` decimal(60,2) DEFAULT NULL,
  `Unidad_medida` varchar(60) DEFAULT NULL,
  `traslado_a` varchar(105) DEFAULT NULL,
  `estado` varchar(105) DEFAULT NULL,
  `fecha_distribucion` date DEFAULT NULL,
  `comentario` varchar(400) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `update_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa`
--

CREATE TABLE `empresa` (
  `id` int(11) NOT NULL,
  `nombre` varchar(60) DEFAULT NULL,
  `rtn` varchar(45) DEFAULT NULL,
  `correo` varchar(45) DEFAULT NULL,
  `telefono` int(11) DEFAULT NULL,
  `logo` blob
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `empresa`
--

INSERT INTO `empresa` (`id`, `nombre`, `rtn`, `correo`, `telefono`, `logo`) VALUES
(1, 'Paperland S.A de C.V', '08011997170626', 'paperland@paperland.com', 22222222, 0xffd8ffe000104a46494600010100000100010000ffdb00430006040506050406060506070706080a100a0a09090a140e0f0c1017141818171416161a1d251f1a1b231c1616202c20232627292a29191f2d302d283025282928ffdb0043010707070a080a130a0a13281a161a2828282828282828282828282828282828282828282828282828282828282828282828282828282828282828282828282828ffc20011080215064003012200021101031101ffc4001b00010002030101000000000000000000000005060304070201ffc400190101010101010100000000000000000000000201030405ffda000c03010002100310000002aa0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000001b58d5593270dabac1e750298cba826ceb75c0d0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000024e5bb63f148f9fd2c186b8f545afdd47d4edcb0c7e4e1b61a45b63736b03e9730000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000170a7de7c950705231ddb32ed679fe5b4a9788d8ed37ad1cb93e575d1ac5eeade99851f420000000000000000000000000000000000000000000000000000000001ebcddcac47f55a81590000000000000000000000000000000000363ef4939aebf51e6a60000000000000000000000000000000000000000000b9536cde6ad286b256eb335fb9dddb8d4262b1e2e6affab079d7d8bcf5ca621ee8000000000000000000000000000000000000000000000000000000000742e7bd0893ae58eb8534000000000000000000000000000000000125d0f9e7431ce3a3f38344000000000000000000000000000000000000000002e7a52ff3eebd07efc7b23ef46e75d17c97cefc1ee80d0000000000000000000000000000000000000000000000000000000000003a173de8449d72c75c29a000000000000000000000000000000000092e87cf3a18e71d1f9c1a200000000000000000000000000000001f71f12d172f22c0003725af6ddd8df05ef52f1f9f54ac95ee8bcb7437aad68f1df3af9392fefe74c5d2a16c43b6000196ee56a72c422beca0af425f072a5fe906b80000fb6e2bf3f66fa4464931050f751cb3cf49a311e00001e8f32562b095adf96119a93c29105d5340e72ddd200007a3cc8d8ec256b7e58466a4f0a441f53d039cb774801bba56b24e0ae980e65396890203724c456958873a8fea3cc4f1d0b9ef4224eb963ae14d0003218e56c9385737a5445e9d8051617aa461cf5b5aa0006d5d21ede53a0ba5f82b129322335a7053203a9699cd9231c00000f5b7792af333c233c4b0ad40f431cf3a1c54a8e71d1f9c1a200000000000000000000000000000005c602d9e1bdba35ca8d0d61f46001bb2c972543e7f4d88399b0f69a2aff1c546db5ac1db2ed5e97daf15e4898fb39b15092ac76c0f6c00f7e2da4b49035606a85d3e52874adbe57672dba5ba397e2ba52c004d933610628aa5173c34c1d1247954d97ac5f721ce743a273c3e0005ea1ae808026222998cba497391d5549ba1879ef4a892800017a86ba02be4d4452f197492e723aaa937430f3de95125000b5d52d65a8079a59678da58bbcaf331d0281f03a173de8449d72c75c29a003d5fa1eda0ad13b114cf05d2579b7d3aa29f6f3579e74e8228e002cd6fa85bc180cd13538d2e7bdcf875550af279e7bd2220a08006dea5f8dfd8793d6856200ba64a38e9f9b975c89f039c747e706880000000000000000000000000000002cfe36a33e7dda68176aa91c3e8401eef70fb7f3ee23e435dfae6c553ec51b7355a76cbfd0ee111e5a8dbdc66a42421a05e9c0f4c8007ae9d41e8620e739e9180000b9d8b9bf483cf32e9f432180e8340ea27ad2dda795df0000161ba72be9c66a05fea85540339d03782379ecf400000b3d6321d45e3d9cea3ec95b0673a06f045f3fe85a4525761495d8526cb23e89d073a8fb2d685aea96b2d4782a759c98c00001d0b9ef4224eb963ae14d03efcde2ffb010f42988700016ea8ec9d33e7d1ccf5a760802cd6fa85bc51aedcbcf200165ad673a7039aea4dc2006ff46a75c4546d9cc0c6001f7e0e872548bb8e71d1f9c1a20000000000000000000000000000000b56aebcf7cfb8998a45aa958c7d1350a458a7f421b9476bfa257aa2daa76163ecf58eb81db2c563ad4a7cbe9ee94f3ee80ed800004cdf2877c1cc7a773b2380001eba9736e9429376a210a0cfd3b96f513ed0ef94d2b80000744e79d30d8add92b85340ded1de3a3839ec64ec100003d9d1f6fc7b2ab54b2d686ee96c1d34068ea93087130871308713087f047d57734c5aea96b2d5a9b7a8735000000e85cf7a11275cb1d70a6812917be7460736d396890000673a5640a756ecb5a00b35bea16f30f30e9fcc00007df9f4ea80a440cf40805aad74fb81abcd3a97303c000024ba1d1af239c747e646b80000000000000000000000000000003edee87b7e7d9282e8117c6ab5b38b53d51b1ae586630ec6e4971d9ea0dfe8de6ad337fdd13fa164a1786f08fa10000001337ca1df057ac23952cd5900161376cff3e9f399dc28e00e87cf27cbb47c80e57f2eb4d3c004a1b179c398572c75c29a06f68ef1d1c11340eab5a29ef5e40163d4bc9908d29d1a00749dca7dc088a0f55ac9507af2004bd84a3a4a34016baa5acb56a6dea1cd4000003a073fba161af58748e6efbf07af23a7e6ab5a482a3f55a99567df800b569dd4f40a7d6acb5a00b35bea16f30f30e9fcc00007df9f4ea80a440cf408048f44e55d0893a6dcb19cb9310e007ab71bf2c1a9cd6cd5900000000000000000000000000000000032d8eaee5bd09cf763cb573f550f98bbfda061d5dfcd2aeb2dba04d4776c97b128dc2b7224f7f30b0000004cdf2877c047921a1be2b0b388e9106ae2a298f5c0002f537cb6e84f6aed0ade2b488797fa0d71b15cb1d6ca701bda3bc74701a1be69c359455e4a580d532f3ef71a0007dbf50321d450b346a42d9455a4a5c7cfad522695211e00b5d52d65ab536f4ce6c000001330c3aaa1a648486ba0e57f2621cf7d0b9d673a722a54d187b30abcac981a26f3c7b29f5ab256c02cd6fa85bcc3cc3a77310001f7e7d3aa0291033d0200ded11d432f3bbd1b5132c2afeeca3536c1158a9278f20000000000000000000000000000000000000000bcd1ac3e5ad0f166ace2d944bed125e47b64000000099be50ef8398f4ee604c4e514747fbcdc5f20ebe3d79000000096b0524746f7cd85fe1ab232ced74746a745001bda3bc747073ccf8234bbeff391d2752822cb5ec60000001310e2efbfce4749d4a08b2d7b1800048c70e8d0158000000007bb3d54744f3cf46c6b801f66e0c5e77b9c0e91a74316180f22db2fcec6e69801bd77e742d754000002e523cf06ceb0000326316599a08e91e79c8bbc04380000000000000000000000000000000000000000007df83a2d0aef03f37a4ac8553765ad0175a57ae43d32000001337ca1df0730e9fcc0c2000000000000000000001bda3bc747073c8d928d000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000009798a83cfb6ff9514ec8c71e890a0000004cdf39df441cc3a57313c8000000000000000000006f68ec1d35e473e8ddad5000000000006ceb4a5728b13d400000000001ed9e1b1aec050000000000000000000000006c6bc9c657236f532c328036706cf93366e4d5d8d7d90cb03675a4e32b904f501b1af275ce304f400000000000000000000000000000000000000000000000048478dad500000000000000000000000377ee880000000000004a45ca5f18b96899467d454954e4467bcdc7e77b563aeefbc71f7ca522f67d66ea49e9e66e7f919b9b3a7932788ebb1a7bda3b339ac8bbe32719bdaf3d714a60c0cddc71f25b91af5b31db5123a4cc9bbf22ef94ac6f84f4ded8c3ad7cb774b04b66be63573c3a937191d76feebe9ee49c66ee1cac0dbcd9b1d97cc89ebe453666e125e2372423e4e332a4a377b01f7d6dc51bbabb9a87d91fb86b9e4d4cfb5b91bbfa49ad9d3d74f49e8ada8cbe694d5decdc7e7e6d5f2849d87de8eb87488edb5b7975baf9b2c76e66cd881cbd400000000000000000000000000000000000000000000000000000000000000000000000000000000002522f6ab96ac9c66d1ab231db2dc3eb1fdcadcd3cdafb32b15b3b75cf1f8fb80f1bf1bb795a727f7e546b62cbaf1d377473ebb64a376758dbf1e7cb32e5d1dddcd29ac38eb9e9f8fbe797a7d790948ef9237c63fcc8c765efe966d767c928dfb959bc6efabe5a1f7cf88ed251bb5b57c5ab9754c7f7cb9f74946fdd9c9e243ed73f515efc65c9c66d6ab1330db9b9923fefc9bdfd4c9876643530c86ce937e3f2b63c79f26313d6562b6335f0d7dc8ddb6eb64ded435a4e3326578f9251b9b2d1bf242f96963908d9e813d0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000003ecb442b9ef6cc762d8fbe48ec0d0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000360000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000007ffda000c030100020003000000210000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000001011c20000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000056814974000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000110005c66800000000000000000000000000000000000000000000000000000000004340000000000000000000000000000000000100000000000000000000000000000000000000000000008e08f2480000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000500000000000000000000000000000000000000000103ac0a8000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000050000000000000000000000000000000018b0000021d96da0c800001802082800000ce20424000004030010200004430000200430820028000000120030000000820030100000000010008500000000000000000000000000000000c950001a2bcf29eb80000420600300800520010b0820003813850cd00038108500d00500028508000000604420110000a04e103200001247003005000000000000000000000000000000007ff0006e5a30d17420000e04000000400300000008100000400010208000200004020504800000000000810000410800a18000400000010000408500000000000000000000000000000000dc288fac2a00398000000a04000010400804000004400000000000808400000000020510000000000010000000400800a00000500a00808000028500000000000000000000000000000000cc85c02815463c0000000a10100328000500800128000000c0000c128428400004000500000000510000224000100000a00000500a00b04200520e00000000000000000000000000000000022faa323a908c0000000a04020010000420430400100000030400800034010824a00404000000508400800c20008200a08000500800008410018000000000000000000000000000000000000000006e87000000000a00420400000000000c10800000830430000000c30430000010000000000800000830430800020000000000010430c2000000000000000000000000000000000000000000040e200000000a000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000008c2040000000a040000000000000000000000042000000000000c00000000000012c0000000000000000000000000ac8000102a8000b00058000000000000000000000000000000000000000000000000042000000000000000000000000000000000000000342a9aafcc5231cb1035881b985854b4a830b7cb869104a6243f068a853802120000000000000000000000000000000000000000000000000000000000000000000000000000000000001c300cc11f2152d5489e1852c637770ad829971c8786b86bae76e701fd71d6a1440000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000344e1000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000f3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cffda000c03010002000300000010f3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf77f35d7cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3820f7a9e3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf2a7b7bebafcf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3ce1cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3043cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3c7886b393cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf14f3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3ce265ef73cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf14f3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3ceb0f3cf3f19e82d6fdf3cf2c70c73cf3cf3053c334f3cf3ca0c31cd3cf38c0c32cd3ce3c30c338f3cf3ce0cf0cb34f3cb2c73c734f3cf2cb0ca14f3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3c3bdf3cfec632a4eaa4f3cb1cd1cd24d3ca3cc1cc3c63cf3ce34e38a3cf24e14e30f3cf3ce14f24f3cf3cb3c528f38f3cf3812c52cf3cb1882c53cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3c588f3c10c9dcc53f9cf3cb28f3cf1ca3cd2c73cf38f3cf3cb3cf2cf34e1ca30c24f14f3c53cf3cf3cf38704f3ce2cf3cf30f3cb2cf3cd2cf3cb14f3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3c1107aabeb7fcdf5f3cf3c728f3cf0ca3c538f3cf34e3cb3c73cf38d3ca3cf3cf3ce14f28f3cf3cf3cf3cf14f3cb3453cf14f3ca3c53cd34f3cf14f3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3c670a7fc2ee398bcf3cf3cf28d3cc1493ce2c63cf34a3cf3ce34f28e34b14334f34f3cf28f3cf3cb38d2cf3cd3cc3c53cf14f3ca3c53c42cd3cc1cb3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf1c36f3fde44fbcf3cf3cf3cb2c730f3cf10f2cb30b3cf3cf2c31c13cf2cd1cb38f3cf28f3cf3cf34f1cb3431cf3453cf14f3ca3453cf3c30c72073cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf2ab79f3cf3cf3cf38f3c73cf3cf3c30c32cf3cf3c33c70cf3cf3c71c70cf3cb1cf3cf3cf0cf1cf1c70cb2c73c31cf3cf2cf3cf2cb3cf1cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3c7b57bcf3cf3cf28f3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cbddfbcf3cf3ce24f3cf3cf3cf3cf3cf3cf3ce34f3cf3cf3cf3ca7cf3cf3cf3cf3edfcf3cf3cf3cf3cf3cf3cf3cf3cd75f3cf7cf7cf1df3ce3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cb3cf3cf3cf3cf3cf3cf3cf3cf2cf3cf3cf3cf3caa9618ebcd7abdc477ef1a38c86f15fa03dfdb9e7e5bd6df65e8b6fcd531c3cebef3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3ca3dbc59466c1f5731e64c2fcf8ae05e65b5d2bee82d34f9c876818f0a37e21ee57f3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3f49ff003cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cf3cffc400411100010203040705050506070100000000010203000411051221310613415161a1b114607181c1103291d1e122234252f01516507290f12024304043a2b2c2ffda0008010201013f00fea60ebcdb292b754001b4e10ad27b352abbace469d20690d9a55775a39f5a52156e59e855d2f0ebcc434ea1d485b6410768eefcd4ca255953ee64915897959bd2478befaaeb40fe80e3bcff0068468c59a945c2dd78d4d7ac2f452cdb84508e353eb8728ec7a3cd82953a49df8fa0a468f4d765b47b2b0bbed2abbf7541a1dbb0ff006eefe983853221236a87427d22c6652cc83294fe507e389eb1685b2f4ada6d4a002e2a95df8923945a2c2e6251c69bf788204352ce322e3b2a5446fbc3a611a37352c99b2c162e2c8c0e3e34c72c3bbfa56d072ce52bf29079d3d6347dd2ed9cd28eea7c091e91a632e80db73415458341c76f2866d9b714d85259bc37dd38f1c0c7ed9b7558063feaaf9c58d664f393e6d09d174f954e14c86541ddfd21b57b7a859d27f6aa712369dc3a931674a7639544bfe51cf6f38d3424a984ecfb5ffcc240480065dda269898979d9799252cac288ce87fc0f3cdb082e3a6891b6276d79bb69c329678211b4ef1c4ec1c333ca2c7b119b311518ace67d06e1d634aad27e5108658374aeb53e14f9c5ab293b2a5026957d3983524639d09899d269061b4ac2af13b067e7bbcf18b2edb97b4c10d5428660e7e3dd9d289e75c71166cbe6aa57cf21ea7ca34419289c771f74539fd3dafbedcbb65d74d1233852a6b49a66ea7ecb29fd79a8f2eaab62ccb153d9a5c5e233a6fe277f4897d309371575c494f1cc72c7945a1212f6c4b015aed4a86cfa6f112d32ab3d4ab2ed44d5a391ddc470e9f110fb364d969d634ad72cfba09040e269d0fc36c68c590eb6a33efe05590cb3da4741f4eeccb8d7e91ad4afc35e49a468b2b513efcbaf3f91fafb6de9b72d49c4d9b2c70071f1dbe491ce2d8526c6b30312d8138576f13e269160582c332e97de4852d42b8e34072a44e58d27388285a003bc0a111a38f39213ae59ae9c31a788f98c62d3745bb69a2599c509c09ffd1f41f5893d179195703b8a88cab4a741cfbb532afd9d6f87578257ea29d62ddb2e6189916948e63303ad3682338634cd9bbf7ed90ae14239d226748672d33d9ecf6c8aeddbf1c87eb18b0ac24d9a82e398b8733bb80f531a5d2ea76442d3f8483e588f58b0a6d33522da9398001f1187d7d96e4bb9316ca9960fda5506efc38f28b1ec76acc6a89c56733e838776edfb23f6931f63df4e5ea3cfac591a49d9ff00ca5a354a93854fafcfe3be0312537f781295f1a03ce1b690d0ba8000e1842d6940aa8d044edb566a10a6dd70281c0818f48d0f72932f21bf7295e7872898986e59b2eba689116025768da4e5a2a180ad3c4e00790eeeda1644ada03efd38ef181f8fce1cd0e5b6abd2cf53c7e63e51fbb76a1c0cc61fcca84e86ad66afbf5f2f5262d8b124aca942e0aa964d054e5c68291a39242ce9154cbb9a85e3e00547cfce25a5e73491c2e3abbada4e5bb801be9b4c4a4a3526d06591403f553de1d2a9454c48de40a941af9647ad62ceb4133b642c0f790820f92703e71a18b4f667135c6f7a0ef11158d1e9a979299986e6141293863c0989fb3acb4853b2b33423109cfc81fef1a2f36ecd48d5e3529245778c3e7de29bd1c919b74bcb4904e7434ac7ee959fb8fc625a59a956c34c8a247f0096692e5fbdb124ff00a20026861c484a8849a8dffeca61a4a12d94ed15e70a42424106a4f2f6942420281c77420052802690feaaf51ac873e3ed99692ddcbbb520fb64da4baf042f2c7a1fe3925ff27f298b3eeeb0950a800c4bcc7697034ea45d3b8529e1027017357705cad294f5df0f3371e2d0df4879f12ca2d320619922a4987825e683e91435a1a65e312e9436d17d42b8d00d95e30d4c87d41a7922870c0508808b8edc3b0d39c4d80261606f89c9a0cbc50d240df86713012e3497d2286b42365619bacb3ae22a49a0ae5e30cbc2695a9740c7220508308656e2ae2054c3b2ceb42f2d341056251090900a942b538d37521e98d7017922bbc61085a1a954aca41554d2b0ca953ae25b7294cf018c1ed40d12d0bbba8226a56eba94a452f530dd0ecc86145a6522830c45498984a1c683e91435a11b2bc21324fac0525268625980b76e398015af9419f20d1291777522d02921b281414f5898480cb446e3d61f487194bc919607cb2f888752196128fc4ac4f86c8581d950789f486585b6ca56d22f295b73a429875e42b5c8a102a0d29e46195a1a95bea4826f615f08726d6e01780c38089e985a92849c8a41c8449b07565e4a6f1ad00d9e30869f78dc7dbc0eda5088b3d4a6a6357e3c81875d53aabcace12c3acb69d4a2a48a934af9087985b8ca96ea2ea93b72aff19957128bf78e6922251c4b6a5151cc11126b4b6f254ac8184a805dee3132e053ea5a0ed8735132758557547314e90eb8c265f52d1a9ad7c625dc45c2d3bee9dbb8c4bb72ed3a955fbc6a2800eb0f1026544fe63d6265695bea5a7226271c4b8fa94938182e27b306eb8deaf2865d6d4dea5dc056a0eefa44b76697702d4bbc7c301c614afb4488249ce02da984252e1baa4e15d8443c86900042af1e50b71265d0807104fa44bbc59702f382dcba8de0ed06ea1ac15dd5de46c38561625e60eb2fdd273047487dd63b3869a391f8e19c5e3be25ded4b8174aeff00082ccb13783941ba98c4cbc1d50ba2800a0f0879c4a9a6d20e201afc624dd42496ddf7559f965130e979c2b3b616e24cba500e209f4843adbad86dc55d29c8f0dc61e4b684d10bbc7941713d98375c6f5797b1c534f3492554524529be912ef22e165d34071077185a1a42490e5e3b29eb58957832ea56aca1f6db41fbb55e101c6e61090b55d50c38110f86d200428a8eddddc06263540a542a939884cc30c9bcca0dee2728249353fd6bff00ffc4003c11000201030107020305050705000000000102030004113105121321415161227114608110325291c115234250a12030334090b1b24373e1f0f1ffda0008010301013f00ff005300093815f0d276ae049da84321e94411c8fcbeaa58e05332db8dd5d68dcc99ce685d499adfb83cf153aef47bcc3047cbf6832f531cb93490868cb75a8c85604d1607986ab856dddedec8f97ed4e24a9c6243568c7257a51861ceb5c183f154d2204e1a7cbf045b9fbc7a91b7d8b559ff0017cb8c8cba8fec0058e05244b08df935a966321f156b1ab125ba544e8d9dd1834b6eec718a96168f5f966d9000646e95767283ed552c702bd36cbe684524dea6a6b471a73a8dda26a65e27ef22d694cb29c1e42ae65046e2fcb2de9b71573ea4561f6c0a22432354238d265aa79d998a8d2925743906ae0074120a8c70232c7534f72ec31f2d2fef20c0e9504aa5786f4d6873e934b0247ea90d4f3710e06956ad87c77a990ab91f642c161cb54b2990f8f96e09786dcf4a96df7bd71d65d79694493ad019a4864272055d8f48275a552c702a72238c463e5d495a3fba685d83f796be222fc35f1607dd5a8a6795f1a0ab87e23ee8a664b718039d3b97393f30dab057e7d6a48ca4a3c9abc1ea1f31ceaceaa5692490e032d5ca057e5f3125c3a0c0af8a92998b1c9fe41b46e1e0e16e1fbcea0fb1cff72c485240c9a81da48c3baee93d3b7f92b1b879a4995cf256c0f6c0a8e47777564c018c1eff00fcfb56473298ca61401cfbf8a95ca21651923a77ab2171c3deb92378f3c0e83b79f7fb767dc3ce65df3f75c81ec31f6ed5b87b6b5696338231fd481fcf36bffd0ffb8bfad6dcdfe02ac6dba4b28cfd6af6c4d8406e6de46df5e6724907be41a3b2cac1c612b71719ce4ebae9a63c55add716d56e1faae4fe556b64768462e6e98fab98009000e9a75f356864b5ba366ec5948de52751cf0413d7c55ebc93dc2d946c5411bcc46b8ce303b64d5cecf36519b8b472197990492081a839a33716db8abd573f98cd6cb62d6311279ee8ad93b38dddaa4b73231ce8324003e9a9ab16920b87b3918b0003293ae34c1ef8357464bbbaf84562a8a32c46a73a0cf4f357568766c66ead98fa7552490475d743e6a5ba8a18f8b2b617cd5bed0b6b96dc85c31d69626da734864622343ba0038c91a9247f4ab4b1f8563b8e4a9e84e71ec4f3a9a19ae768bc41caa6eae7079f5c01dbcd5d471ec981e7849c9c0e64919275e740ece2bbd25c92ff008b788e7e0698f15b3b68992da4790ef70f3cc7500641fcaadb679bd8c5c5db92cdcc0048001d00c5593cb05c35948dbc00de5275c67183df06a4dad6713147900235ada17861b6e243ccb602f6c9d0feb4362a95cbc8c5ff0016f1d7c0d31e2b61ac8a67598e583f33df90e7564cc6eee413c815ff0088ab37682ea4b573907d4bec751f4356ced7578f303e84f48f27563f4d2a1663b4655cf2dd5fd6aeaf219ee9e1b89771139601c163d727b0ed49796f69327c24dbcac402a493af519edd6aee29ae3697051caaee64e35d4e9dbdea0d990c04942dcc60e589ff00d3e6b6358c4924b20ce55d80e674e5e79fd6b6a5ea8b85b579386b8c93d4f3c000f4f3535cda5a2f1aca7f50fe12490de39f5ec6b6e471dc58f18f4c11cfb9156d6d1db270e3ce3c927fde9ef2deee67f8b9b7554901412338ea71dfa55ade436f74915bcbbe8fcb04e4a9e841ec7f9ced28249b85c319dd7527d866b6940f32208c670ca7e80f3ada90bcf6924718c923953a130941ae31fd2b67c0d1d9c70ca3985008a87e3367af0163e220fba4100e3b107b77ab682f1efc5d5c2800a9180738e6391ee4d5f412f196e6db05d41183fc4bff0083a55fcf7d716ee822e18c1c9241e58e6001dfbd5a296b0451a941ff001ad9d0bc5671c4e304002b6542f05a471c83040a103fed03363d3b98cf9ce6aeede78e71776c327182a796474c1ee2b687ed0bf81a248b7075c9049f031fee6a34c46aac3a0a0aa3414d15cd94cf2409be8e72467041ea467506ad26ba958b4d1845e83393f5c72a86075be92523d24281f4cd5f5a0bb81a12719d0f623983427be41b8d6e0b77046e9f3dc7b52c45e1dc940c91cf1a73d7150b5ed8a08385c451c81040e5d0107b559dbddfc71b9b90002b8e5d39e9e7be74a28a799157d682ee0316707507b11cc50bbda0a371a0cb77de1bbefdfe95b3ed1ada33c4397624b1f27b78ab48248ee679187262b8fa0c56d6b69a4559adbfc443cbd8f223f5fa558da8b481611d35f27a9fcea281d6fa4948f495500fb66a6b79eda769e040eafaae8411d46797b8ab47b89642648822fb8273f4e4050824fda066c7a77319f39cfd9025c5a5c3a08f791db7b208e59d720fe957b6b2f196eadc02c06083d46baf422a196e6690030045ea4907f202b695ab5d5b3c29c89d3dc1cfe95673cd2a9e3c7b8479073ed8a304f632bb431f1118e719008275d7506acda790969a3083a0ce4fd71cbe40bdb1f882b246dbaeba1fd08ea29ec6f2e870eea41b9d428c13ee49e5f4a550a028d07fad7fffc4004c100000040204070c07060503040300000001020304000506111214102021314162a11322233234355152617172c1303342738191b11516508292d1244043536044457054a2f0f163b0e1ffda0008010100013f02ff00ed6964c1cbc1e013110eb0e68428c8d5c3b8f81020f4653ab78e0f5f6960b4656af7ce1300ec085e8d2e52d68ac438f40e48468fbd538d613f10c1a8d3900c8aa423f1878c97666a974c4bd03a07fe4091c937602aeec383f649d30aa88b446b389524c21cd254ca3536444fda6c90952635ae19b855aa306a4a855bd4141186d489aa991629921f9842d3e629f14e653c2109d23686354622840e98315178daa1b2a227089ab233076298e526728f487fc7d20637c7bbf0e093df1bb7b21fba4d93532aa660cc1d230f9e2af5615163770680c4210ca1c0840acc3900212a34b98802a2a9907a33c05193e9725fd312860797a674c57dd0839402aaaa8a6167736dd7ac7e5ff1f51546c4b6de950d5c52c70277844038a9857f11c0d9051cac54910ace68984adc30294cad9120e4acb828b900d35011f64a2210f1632095b2227586baac960666e744b1c42b36744209becd543be1f3b55eae2aac39740747f882699d5381132898c3a020d2698149685a9ea81c8397f106ac9cbae4e89ce1d2010e99b86bca113a7de1f8dc839a1bf70fd629073bb8ef0fa60a306aa6c4ed2884525209a50a55a040703074666ec8b172d9ce1d21084e192a4036ee52761f247da6cbfea92f9c37728b8af7050a7ab3d98a50d4883d2a898540a85621dbfe21445991297838ab85574f660a66cc89a893a4c2a13ef4df87cb9bde9f2286839aa18452222915348a05217200043845370899258a0621b3843d42eced6473d83097f1aa2ea5b95817490c2114a92b132b7fdc280e066b0b67492c1ec1ab81dcddb6eb24a17642f475d1541dc44872681aea8fb01ff509faa3ec17ff00da0fd4105a3ef87d9207e68914b8f2f4d4dd0c5318f566d114c0c1bb372e90288ff8851be646ddc3f5c14db90a1ef3cbf0fa3bcf4d7c5e5867bcf0efde0fe3543c46cb90d1922988656a3a77de5868ea0746584b6611b7be00e808773768d5514d43889c338142baa027ec3ae7fd31f6eb0fef0fe91834fd807b671ee2c39a4a4001bb22611e93c395d472b19558d68e3fe2146f991b770fd70536e4287bcf2fc3e8ef3d35f179619ef3c3bf783f8d51b6866cc6d28151d51b5576452c580ef48907f4cb97bc70142b100828026900682842c7151639cd9cc35ff8ad1be646ddc3f5c14db90a1ef3cbf0fa3bcf4d7c5e5867bcf0efde0fe3323925565c3c2e5ce54c7ce266fd360dc4e7ca71e297a61650cb2a65141acc61ac7027c72f7c29eacddd039ffc568df3236ee1fae0a6dc850f79e5f87d1de7a6be2f2c33de7877ef07f174123aea0269144c71d011279291a54aaf51d7d8589accd2609e5df2c3c5243b72abb585558d59876609348c8748abbc011b594a4fde1593b1396adc009da583fab37740e7f492f903c7600612ee49f58ffb437a2ad8bebd55141ecc904904b8bfe9ebef30c1e432e3072700ee11871459a1fd4a8a263f387f479e3501310016274933fca3367f452f94bb7d9514f79d7364086d45130e52b98c3d040aa13a3d2e27f444de230c0c8a5c3fe98bf3185a8c31387062a263d835c3da30e5201336302c1d1986144ce91c48a144a60d03e8e5f2678faa14d3b29f5cf9021b51444bca1739c7a0b9213a3f2e27f42d779860d22970872600ee1185e8bb23fab32898f7d70fa8d3b4004c8082e5ecc830729886129c04a60d03e8a5f2678fb2a69d94fae7c810da8a225e50b9ce3d05c909d1f9713fa16bbcc306914b84393007708c2f45d91fd59944c7beb87d465da1be404172f66418394c43094e02530681c6944bcf317609106a2e731ba0211a3d2f4d3b228db1eb186290c8819a5786b58a5ed147d982944c600280888e80863469db8a8cb54817b73c2145d913d6994507bea82c8a5c01c9807bc460f47e5c6fe855dc61871455b183805544c7b72844d252e65c35aa1693d072e6c346f991b770fd70536e4287bcf2f412f91bc7951809b9a7d63c37a2adcbebd65141ecc904904b89fd0b5de61834865c60e4c01dc230bd16667f5475131f9c3fa38f1b00992a9726ae7f9408080d42150e3cb1928fdd9504f2579447a021bd1e97a49d9325ba0f58c314824056a88b9675d82f1883a21ab559da961ba6639bb219d1438800bb5acea9212a372f26721cfe2347d872dffa52fcc616a32c0fc405131ec343ea2ee1201335382c1d03906154ce89c48a944870d03e888531cc0520098c3a0218d1976b801971040bdb94610a2ec89eb0caa83df5416452e00e4c03de230a51f971ff00a167b8c30e68a226e4eb9c83d06cb130933c6358a89da4fae4ca114779e9af8bcb0cf79e1dfbc1fc30a513180a501111d0104903e3256ac90a3d511cb0729887129c2a306410f412e97acfd5b2905450e31c73044b9822c52b2906fb49c738c4de78442b49a5475749b416143995389d43098c39c4702356ec4b5c5b415c1abb0362aaeac90d674e9abd39261598b5ef83abdd043916480c435a218338438a3ce01e5842a14472db1d10ce44d102f0a5dd8fd26cd0f248d174c77320227d02587281db2e74950a8c5c76add574b95240b68e31279120c400ead4ab8e91cc1dd8f3792b798144c0009afd70d3df0f5a2cc9714972d46fae394a263014a1588e60089251c2a60559f85a3e84f4077c000005419031a632e6f304ecae4df683867089bca9696ab51f7c90f14e1a7d026432aa1489944c7364000892d1e4db802af40145baba0b8f3495b79813852d4a683867089a4b96972d6160aca3c538661c74c8654e52265131cd9000224b47536e00abd00516eae82e3cce56de604e14b529a0e19c22692e5a5cb58582b28f14e19871683872b1d3bdf3c0f90bcb3591aeab65b312b95379793832da534a839f197488ba274940ac860a842152d854e4ea8d5828df3236ee1fae0a6dc850f79e58cdd151c2c54912898e6cc011269022cc0147152abec2e3cda4ede6051110b0b68503ce260c9660e0525cbdc3a071a8407f12e8754302c995548e99f8a70a861ab645a2409b72010a1d18d3097b77e9595c99741833844e254b4b55a8fbe4878a7f412b972d315ec221bd0e31c73044b256de5e4e08b5a9a4e39c71cd25402628bc43833146b314330e19ef3c3bf783f86482580d1105950fe20dff006e09fd5f6bb8aba7cb1e4f2c3bf52b1dea25e31a134d166daa2d94d22044e2766715a2d6b223a4da4d8b209b02e42b670352c190a3d689ccac8fd3acbbd5cb98dd3d912c7eb4a9c8a2b80ee55ef89d1da11f6ab1dcedde4956d85e72e1f2e0de5a4b35fb639fff00c86c99924084514150e0194c3a6291b84dc4c781ca040b223d38c8a675952a6985a3986a008924ad396b7ab8cb1b8e6c2f1f36661fc42c52766985294b228ef08a9fe154274a599877e9ac5f843398b579c9d62987aba70cda5e94c5b0a6a6430714dd10e9ba8d5c1d1582a3971a8cc9c1b260e9c1787371407d90c2e1c24d896d750a42f68c2f4998a63bcdd14ee082d2a695e54560f94339cb17636535800dd5364c2e504dca064962da21a2712e3cb9d8a66ca41ca43748630056350678a3b27062902cb07f1260fd3d985d3a41a92d3854a40ed85a93b120ef01553b82094a9a08ef92583e50ca6cc9e0d48ac16baa6c8385f3449eb7322b96b28ec899b1525eecc8a9f94dd218a1946a08a3928062902cb07f1270fd3d985cba41a92d3854a98768c2d49d8907780a29dc104a54d0477c92c1f28673664ef222b05aea9b20e17ad5278dcc8ae5aca3b2268c5497bb322a7794dd218941f33bfcbe78af26cc9a0d4aac16baa5ca307a54d0077a92c3f284a943238efcaa93bc21abd6ce8b5a0b10ff00189cce11628180a729dc0f14a102358d63828df3236ee1fae0a6dc850f79e58a42194394840acc390022432a24b9bd66a85c1b8c6f2c2edeb7685adc2a5277c2b4a1914778554ff084e953311df26b16194cda3cf50b144dd51c838666c52983614950f09baa30f5aa8cdc9d05837c5db8b41fd7baf0862088142b30d410ea90304044374150da815c7deb6b5fa95b64349eb072205056c1ba0f930ba6e9ba40c8ac5b44344d981e5ef0c89f297394dd218d2c64a3f765452f88f40431689326e54500a8a1b70bc9b3266352cb05aea9728c1e953401dea4b0fca11a4ec4e3bf0553ef086ce90744b4dd529c3b3167bcf0efde0fe1747dadea644b5c426fc626aeee4c9457dacc5ef8635dc90b435984802231333db983937ff20e34a581dfb9b019130ca7374414a8b36d50549a29844e66a77c7b04acadc33074f7c4aa46a3a282ab88a6968e9184252c910c8814dda6cb0697b4306f9b25fa61e51e6ca008b71148ff003087acd664aee6b96ae81d03002203586418914e2f2008391a97d03d6fff00626f2b4e604af88b06637ef01217dbad8b05abaf5e48964bd2609594f29c78c7e98a4138b2066cd4d9731ce1f4c7a1d2fa882f540ca3bd4ff7c33ea422998cdd80ef832194fda0e73286131cc2630e91c253094404a22021a42245488d68a83f1ac07202bd1df8697cbc166b7a20708971bb4b8b4565f7b7bbaa81c12397bc74619f4ecb2f0dc91a8ee47e45872e5674a8a8ba827376e249a7cb333026b88aadf69610548ba45512301886ca038278c0260c0c4fea177c41ed810a86a1c5a212fdddc8ba503789717c5867f3d06422835a8ce348e8242eb28e141516389ce3a47124b48156c62a4f045443ada4b043954201c820628e5010c14925f7e60612870c96f8bfb62d109782ee45d281bc4b8be2c33f9f033116ed2a32fa4da0b0baca2ea09d6398e71d238925a40ab531527622a21d3a4b099caaa653a660310d94043052397df98184a1c327be2fed8941f33bfcbe78543953218ea0814a5ca22313aa40aba319268229a1d3a4de828df3236ee1fae0a6dc850f79e58b43a5e06133d50336f53fdf0cfe906e0633764202a0718fd10aa8754e2750c2630e91c20220358641891d223a662a2fcd693cc0a690ef801ac2b0cd82964bef2caf040e151da5c5a0febdd78430bc749336e65971a885db1369bb8989c6d0d847426189269e2cc0c04504546fd5e8ee841522e915548d68860ac070526637c9718c50e152df17cf1a8dcbee4c00c60e195df1bf6c0a1ca9904e71029432888c4ea902ae4c649a08a6874e936220b28dd403a27310e1a422413d07a20839a8ae340e83624f79e1dfbc1fc2e87a7c1b857b40b14c15e4e8f79a1a72647c01f4875ca96afae38a92665542a640acc61a802258cc8c5a9522f1b3987a46290ccef2aee088f0241cbac314725e0ed715550ad24f4748c3f789316fba2bf028698793b78e0dbd3ee44e8241264f083595cabfaa2574804c704ded597fa81e7130689be6c2929f94dd030b2664553a6a054628d4312d40ce5f2299749be410baa54113aaa642142b18fb6d859af77f859189acf8cb145269590839cfa471d220a8a9085ce61a821aa256edd3449c5216ac14aa622d1a02290d4aadb031e88cc857445a2c359d30de8f4970284050862182b2982a187888b674aa23ec1843128db6bb4a51c9be537e3f1c1387a0c181d6f6b3143a461550caa863a8368e61ac471a894c450737450782578bd86c349db5da6ca590a8aa6fc3164adae92c413ab7d55a3778e09fbffb3d818e5f5a6de920c2263098c3588e9c6a1f3112a9725477a6ca9f60f4619f36ba4d574c38a2368bdc389246d7496209fb555a3778e0a4330fb3d88893d71f7a4fde044446b1ca38d4466429ad73547787e2760e19f36ba4d572071446d17b870d07cceff2f9e1a6131132b724877a5caa768f47a1a37cc8dbb87eb829b72143de79621404c6000ce30c1b835668a01ec16ac149a622c59594c6a595c85ecedc7a1f31150a2c951ca5ca9f7746010010101cc31326f747eba3d4364eec4a0febdd78430d2b7e2e5f0a051e091c9de38d439f891716671de1f293b070cd5bdd662e110cc5364eec491b6bdcd104c78b5da377061a5f321329724877a5caa768f46314c253018a35086618904c3ed060539bd6937a7c33de7877ef07f0ba27cdc7f7914b79c53f761f51892adbb4adb9ba0b647e113f43709a2bd53efc3168bcbc4bfc5ac5f77fbc5247f756bb9263c2abb03051e4c1394a357b5be18a48e056999c9eca5bd0c4a34e85c4bec9c6b324367e114a9bd97e9a850f5a5db147e59734b75583873ffda114a9f54406898e51ca7f434713dd274d83a06d7c830d2471789c2fd041b01f0c79338baccdba9a2d543dc38696276272a0f5ca06c2996da852f48d5042d8214a19802ac14d5c5a728b70cc52da1f8e394c253018b9043284335af0d115bae50360a709f2553bcb88c53dd5ea09f58e0186993815264547d948bb471d054c82e4549c620d61091c144c870cc60af05364ea76dd4eb12af97fef0b24f7578827d6380619a4a119928432e7502c05400518fbaccbaeb7ce3eeb32ebadf38fbaccbaeb7ce3eeb32ebadf38fbaccbaeb7ce3eeb32ebadf384a8d344952a8451603146b0cb869ba753b6ea758957cbff0078683e677f97cf02a704d339c73142b85d532cb1d53f18e358fa1a37cc8dbb87eb829b72143de79624913dd66cd4a3d701c34a9c6ef3750beca5bc0c7972e2d5f20b07b26d98698a7626d6bae401c4a0febdd784302a6b091cfd50ae0e6139cc61ce235e3315450788281ec9c070d2f2599c08f58803894253ade2ea754957cf02860226638e628570e1532eba8a9f8c71af1e873814e66297b2a9768619ef3c3bf783f85d103d6d5726903d714bd3a9c20a7496cc5157a09aa66aa0e43e52f7c4ea5c0fdbef722c4e28f942e8a8828245882530681c04218e60290a2630e808944846b055f07727fbc3d749326e2a2b90033074c3e74778e4cb2b9c747460911ad4a5b775513f20926ce2bd235e250f370ae4bd803074533aa450e5013938a3d11389a11827505465c78a5e8ed1850e655431d41acc6ca23e868973d27e137d30bee5abd79f7437d71c9c72f7c0660c14d39d53f741f51c2c79737f785fae1a5bcf4a784bf4f4147b995ad7d5c14df9137f79e58924e7769ef030d24e7b755f487d3d04ab9b1ad7fdb2fd30538ff49f9bcb0c979d9a7bc0f4b4e043f840d3bef2c341f33bfcbe7826d9258ebdd9be9e8a8df3236ee1fae0a6dc850f79e5894639f1b7c7e83866fceaeebfee9bebe81bf274abcf6430537e58dfc1e78941fd7baf08607bc8d7f00fd31cbc60c34cb9d4beec3cf1283ffabfcbe78269cdaeaacfb91be9e828e73db5eff2c33ce7777ef07f0ba30e77198d8371550b3f18a40d2f52f3590ad44f7c1002203586418944f48a94127a3614ebe81855145c938421142f6e58fb1d8575ddcbf31845ba2dc3814c89876044c676d9a809531dd55e82e687af167ab6e8b9abe80d01868939b4dd46e3c620da0ee8a56c84c52bb207177a7eec4a1e1fc4b81d4f38a40fd462d89b8d56ce35563a2143994389ce613187388fa2a25cf44f09b0d2142ef37701a0c6b61f1c795217998b74ba4d97bb0d2e52dce4e1d428170a46b0a90dd035c146d1404330e0a6a8597a8afa0e5abe218e0158d419e18237764823d420060a6dc850f79e58923e7769ef030d3043739aee9a15280e3a4415552909c630d41089372448986629403053752b72d93ea9447e7ffac2c14dc9f373f54e03b70bf9a35607295c98c5130561bdae3ef1cbbfba6fd031f78e5dfdd37e818fbc72efee9bf40c7de3977f74dfa063ef1cbbfba6fd031f78e5dfdd37e818fbc72efee9bf40c7de3977f74dfa0615a4cc08411209ce3d00589a3f5260e85657268297a030d07cceff002f9e09bf35baf766fa7a2a37cc8dbb87eb829b72143de79624894dce6ed4daf561a4e86e1385ba14df863b1445cbc4510f6cc0119b0537e56dbc1e78941fd7baf08607bc8d7f00fd31cbc60c34cb9d4beec3cf12842953b709f5895ffe7cf02a4dd1239073182a8548292a74cdc628d438f44101566bba7b2914470cc54dd5fb83f59411dbf8594c253018a35086508953d2be68553db0c870ed8a4129140e670dcbc08f1803d9c083b70dfd4ac72770c7db2feae506f942ef5cafeb5750c1df8ac5c9d9ba22c4ce5d1d2109988e9b01b3a6a17643c46eeed54ba8610c3451bee6c4ca8e75472770452e5ad3a4910f60b58fc7d1d12e7a2784d86974bc5c360729056a25c6ed2e3d0f97090867aa8653644fbba701840a5111cc10fd7bd3d596eb9847128f39bd4a50357be28581f8609e31fb425e74c3d606f89df062894c253054219c31a8acbc5d3d05ce1c0a397bcd869b72143de796248f9dda7bc0c348e5f7e603b9870c9ef8bdbd98f4425e2ab8bda81c1a7c5ed361a44e6f53658c1c52ef03e18928737b9720ad7944b97bf0525978be635a615ac96f8bdbd9e9683e677f97cf04df9add7bb37d3d151835a9237ecac36e0a684ae5a91baaa796210c243818b9c06b0866b8396a92c5cc72d78295cbc5db305920ad5476863d0e970d62f550d54ff7c34df95b6f079e2507f5eebc2181ef235fc03f4c72f1830d32e752fbb0f3c4a3ee6eb3640e3c511b23f1c34be5e2938bda61c1a9c6ec363d1b97dc1805b0e194df1bb3b304d5cdd25ebada4a5c9dff0086cb9ea8c5c6e89e6f68bd30c9da2f90b688d61a4a3a226347d35844ed07733f547343a973a6c3c2a26aba432862a4d9757d5a4a1bb8b08489f2b9d304c35c61bd1a0ff50bd7d84086e8950448927c528541138381e68e44335bc129971dfaf5664838e68399268d44c3bd4932c3c5cce5ca8b1f3986bf4744b9e89e13624fa8f9886338625b49e714c3477466cf8922901dc18ab3c28911d051ce6828014000a150060a58feecc77020f0ab64ee2e2d117f77762dd41e0d6cdd86c3486457cadc35a817f68bd685533a47122a512983380e249e4cbcc0e0350a686938f94346e9b540a8a25b242e1a6dc850f79e58923e7769ef031290c8377319cb20e1072993e9ee8394c43094e025306701c492c8d67e603aa029b7eb74f7420911048a9245b242e400c13f7e0c1818c03c29f7a4efc5a1cff7354ccd41c87ca4efc348241bb18ce5886fc72993e9ee8394c43094e02530681c493c917980db3569b7eb8e9ee8fbb0c2c55c2d7d6b513b959e58b0008db48dc5362d07cceff2f9e09bf35baf766fa7a2a16b5b972896921feb8272d6f92d5910e308565ef810a86a1cf89435fd64332507286f93f30c33fa3e6b4670c0b5d794c9879408080d42150e248e42a3b315574029b7e8d268210a9900840002864000c34df95b6f079e2507f5eebc2181ef235fc03f4c72f1830d32e752fbb0f3c5a3efc1fcbca223c2937a7c0ba445d2324a96d10d9042275245981c4e900a8dbadd1df884298e6029004c61d01147e41b818ae5e87099ca4e8efc34c5fdb50acd31c84df1fbff000e6ce156ca02881c486ec86348c86a8af09647ae5cd083b41c0702a90ff1855a3757d62099bbcb1f6531ff00a64e0258c833364be5046c813888a65ee2c1d64920dfa842778c2d3a6297f5ad7802b86136bfb9b08226dccb94c730c4c1c83466a2c3a03277c08d622239c625121dd932acf0440a3940810008b36feca49122793517c7dcd2ac1b97feef4944b9e89e1362be94337b956482df58b90615a26908f06e4e1de5ae13a269d7c23a30f71618c999331b49a568fd63e5c3317a9306c65961ee0e9187ee947ae8ebac3be36cc50c839228e4dc1f2208ac3fc4903f5074e178c5b3d2d4e1229fb74c2f451b987815d42760e582d122d7be7635761219d1e62dc404c4154daf00000150640c4a6dc850f79e58923e7769ef0315f4b9abd0fe212030f5b30c2d451011e09c1cbd8215c128916bdfba1abb090ca40c5a881b73dd4fd27cb85cae9b644caac6b242e7189c4c0f31762a1b2103210bd018a53090c0628d460ca031209a9662dea3d40e09c60e9edc2f65cd5ef28480c3d6d30b514404781707276085705a245af7ee86aec2433a3ec5b0818482a9ba4f01903260a6967ecc4ebe36e8157cb1683e677f97cf04df9add7bb37d3d1515797599810c3bc5b79f1d18669479bbd5455218515073d419061e51770926264142ad57b3554302150d43907022a9d154aa24364e51ac0624d324e64dad0645438e5e8c2fa56d1ee55d20b5d60c830ad134847827272f785704a264af7ee8c21d858652462d040c54ad9fac7cb8b4df95b6f079e2507f5eebc2181ef235fc03f4c72f1830d32e752fbb0f3c5944c0f2e760a97290721cbd210d974dca0555135a21b0bd903174226dcf723f493241e8916bde3a1abb4908d1440078570737600550ca5cd5907f0e90147ada70cfa6a59737a8b50b83f14be707318e7139c6b308d623f88a6f5d27c470a87e68fb59ff00fd49e0668f873b953e70774e14e3aca0fe6c346d104a5698e9537c314a1e8aaeaec5f56967ed1896240bcc104cd984d961e2d7668aad5710b5d50f5fb87a6ad73d61a0ba03d2d12e7a2784d865f356ef4e64ca3656288809071e69346f2e4f85356a684c338c4ca60b4c17dd171f09433063a4a1d250aa266129cb940422494813740549d0826bf4e8363bd788324b747070286d1897b9078d135ca1640fa30537e44dfde796248f9dda7bc0c294d1b99faad0c6b0b106a0afdac77ef906295b707aba0348c4e26cb4c95df6f510e2931dbaea3758aaa26129cb98624b3d45f0026b549b8e8d06eec77ef906295b707aba0348c4e666a4cd703182ca65e2171683e677f97cf04df9addfbb37a2cd14766c57e8026a8ff1240cbaddb894ad22a53852c7b40061efc2cdd2acd72ac81ac9c36c49e7484c0a051a9371a483a7bb1e67336f2f4eb58d59f410338c247dd1221fac15e0a6fcb1bf83cf1283f2875e10c0fb2325fdd9be98e5e3061a65cec5f7618d279aad2d577bbe4478c4860f907c95b6e7afa4ba431e753d45880a68d4ab8e8d05ef870ba8e5632ab184c7369fc6240703ca5bd5a02a8a4490a5365abcc7df0448b9ddb78a279cd2e7c3e9a8973d13c26c2e4c257aa89444040e3943be185257480015c002e4edc830de92305437e274875820b35606cced2f9c1a6ac4b9dd25faa1c523609714e65475421fd2770b0095a94112f4e71839cca1c4c73098c39c47d14ba7af19545b5baa5d53c35a50d14f5e53a43f30824e25e7cce93f88d503336219dd23faa17a412e4bfad6c75021f52a50c02566958d63e51870baae54b6ba8639ba4628d4e88cc97675912aeb29ba20f3760525b1749d5d83148269f69390b00208938a03f5c491f3bb4f781869173dbaf17944ba903b69514e3bb27d07cff386d49d9281c281d21ed0ac20937607cce92f88c1a68c4b9dd23faa17a432f4b32a2a0ea043ea52a9c04ad1204f58d9461759470a09d6398e71d23e8a5d481db4a8a71dd93e83e7f9c36a4ec940e180e90f68561049bb03e67497c460d34625cee91fd50bd2197a599515075021f52954e025669027ac6ca30baca38504eb1cc738e91c6924c465aef74aad266c87084a70c154ed0394c3c4350c5249e26ba22d598da29b8e7f2f46928749403a66129c330844ba94d45023f257ff00c84fda129ccbd50c8e481e2c90f276c5b2623bb1543682932d70f9d1de3b517538c61f96200d4358641897d2376d800ab70e4d6cff00386d4958aa1c25b487b420936607cced2f9c1a68c4b9dd23faa1c52397a5c539951d5087f49dc2b595a94112f4e71850e650e2750c2630e918a3f3e44ad88d9e9ac18990a71cc210e272c104ed0b821bb0994626cf8d307a65cc1506628740624a1f9a5cf01600acb98c5e9084272c164ed039217b0d9062914f5251b99b3235bb790c7d157a091cfd0337222f4fb9a840aad0e6343b9db16e989b772a83a0a4ca231307677ced45d4ce6d1d018e82ca20a01d1398870d210c694aa4002bc4814d62e4184290cbd5cea8a63ae1059a31366748fea83cdd8133ba4be030e693b24c38103aa3d815044c6903c7759483b8a7d04cff003fc6a8abd021ccd541e365277c4f9815e35138070c98565edec89173b36f144dc865258e0898098c25cc10721c9c7298bde1e968973d13c26c2ef95ade31faff003d23e7769ef030d23e7a75e2f2ff0084ca2251010c8210d8fbab748fd6280c2a266afce29e4326a64f9c4be72d9d102d9c1257494d03b9aa5cb64e1f38a472b2360070dc2c9046a317a3d2512e7a2784d85df2b5bc63f5fe7a47ceed3de061a47cf4ebc5e5ff000a4ac40d2e6c21fdb08792074b3b59429d2b273088658fbb8efae97ce2512774cde9153a84b019c0073c524100942b5e910abe7e928973d13c26c2ef95ade31faff3d23e7769ef030d23e7a75e2f2ff8524f393312ee4a96da3a3a423ef1b3eaabf28fbc6cfaaafca0691b4ab2115f944de68a4c0e192c245cc5f4944b9e89e136177cad6f18fd7f9e91f3bb4f7818691f3d3af1797f23751b9de2d0555e6fc5d26a2a35516b4151347a416472b4ddce205e82e35d46e778b415575558ed5a8b82a820600b015ff81d1f7256935454506a271447a2bc0e972366e75951a8850854dba2873f586bfe7982d767a82c398870184ce555329d310314d940420e602144c7100286511189a38075315d62f14c6c9fc8ff00b1fc7cff009244c0454a6305a001cd0f9622eb5a4c9642afc11a73438efc0cdca68a4a14e9da1363315c882b69425a0aa143019431802a011cd81a1c89ae53a85b450d10f1d19ca958e428660c60e631eff3c793fab73ddfbff82349c3e6a9d84971b01980d961ecc1d3de52a98c01a347e00ca66ed916cb75840bd51ca10f66af1e16caeb0893aa1903f92ff63f8f9e096b7496647dd03dacf0674c921b0442d874c1daa2f13dd1a6f474841ee8cb7824dd54d305068f804a42ee4ac2a414d4310d9c3035669951dddd8d44d0117d681901b6f7ba146a83a48546790c1ecc66c0834491441679a73162fad335db7bdd0b34497485567a33970370032e980e613044d88523ba88000156882e70870cdb81f7552a2a650cc117c661901b6f7ba166a8ae88accf4672e000111000ce3056edd9a60675be507d98be33364336a8bdd0f199372ddda8d69e90e8c566d8ce54a83214338c19564db7854f743069829d93ade989b91ba615281153140d680073c4a08551d081ca060b3a605bb669be71be30e62c5f5a0e4336c9dd054af0e84adc2a288e4af4041ae6cb7a62eeaa6982b864bef5446c76c3e682d8c02036931cc381068920882af34e62c5f5a66bb6f7ba176892c8eeccfe25c56c819c2a0427cfa20d7267bdb1baa9a60abb27036544b73ed8337bb4bdc16bac07286096a643b6722728088060dc88bcb00c9940144f3e0628eeee4a5d19c626424bd18122800064c912921547551ca021674c2c152a700cd58c3640ce1502120d7267bd12eeaa6982aec97dea896e7db0f9a0b6374a6398625a503bc20182b0851a3640e755c714477a508beb4cd76def741d4441ddb4c9c157c518bc2570dd772de57c4878ba6b18bb9a409d58136a8b648147994c398b17d69985b6f7ba1cb34d447776635974970305d351352c2404b2197b61dba4554aca680107a7031682e4fd040ce3065d921bd4d2dd3b60b7279bdb3b929a21ca066ea890ff003e9ff32ff63f8f9e06a3549d7aba7f6c1241fe20e1a2cc2e35ac711e9868353a4843ac1138e5a3dc10dcb6d74ca398462727117004f64a1825ca0a6ed3ab48d431352815e9ead39618900eed228e6ae26ca09dd8974172609528247650d06c830fca0478a8066ae1af294bc41139e59f9420bc6089e1c6da64d15578250712bb02e83043c2811d2a019ad449c8067815fb215c3d505572a187a6ac1253f0874878a60ae152d950c5e81aa1ab151c276c82500aeacb1f652dd6243848c82a299aaac3a2131dc64e262f18e38924e566f043d505572a187a6ac1272700b189c71c81032b5c46b1312b8fb296eb120e81c92b51358406ce50860503bc4807357135504ef0c1a0b90304a54123b29741b243e2011daa019ab86cc145d2b6512d5db1f652dd6242c98a4a9886ce112fe0a5ebac1c68cf811505493ab5fb39304ab92baeec1295b737160dc53e487a8ee0e0c4d19c219ff08c0eb8f1cfc5c125e563e185fd7a9e21894106eab18956e839023eca5fac48fb296eb121540e5959c8b080893280c4ab9727134504ef0e0398b903087318f7f9e096100ef53af46587ac5770b89ed16ce88fb296eb1225ed166ca0da3144839c21d1408e54286603449fd5b9eefdf0a289cd2b291110013e5118fb296eb123eca5fac489b906ea8994ab740c83fe65fec7f1f3c0df99d7effdb0493949bc30afad3f7c36e5297882273cb7f284266b0a14c1a06b899a3bb90ae51df0559704a9a98eb02a60de176c4e3968f7043753725c87e8189a216c41c25be21832d58250d4dba6ec70a8a19a267cb95ffcd10d794a5e2089cf2cfca105e3044efd7a7e1c12ae5c9c3fe58af7c4b96045d14c6e28e41899b61496150a1c19b2d78250d8c980aaa0542390021c7af53c4300610cc22116cfd637ce33e786153962a3611a8c1942144cc91c4a70a86124cea9aca65130c18a243094c15084493959bc30b7ae3f78e0942803ba2061aade68704590504a71377f4c145431aa2898461605486122b6807a061b29b92e43f40c4d1b898d784b7c43065ab04a1a9adeec70a8038b133e5cac01843308845b3f58df3c12a394e9aad8fede685d13a0a094e104298e6b240ac61cd4d25c08ff0050f9f04ab92baeec0192154bed06e8285e35751a26ea80a85449c54f0497958f8617f5ea78862527031156e61aade685cab20a094e26f9c105439ac944c230a828430915b403d0312ae5c9c4c396ade2c2886e926394b94407033577172438e60cf13348e53eec9888a66cb93445b3f58df3831572260735b028e61c125cbbb93488418a253094c1508606bfc54b85128d4a93341c552184a61300c240b2a6a93b6618309aba8c2393a7fccaf5fc05dece5af3e04dd5864a216729873e062e2eca8984b6b255061b4611e984cd61429ba06b87abde57b601564ab03476a361dee52f5463ed0407299b05a85662a1ce5a82c9006bb21a61eaf785c4e015400563506786ee966622410c9d5347da0867bb05af8422f5472f520e292bcc1133e5caff00e68848d61429ba06b87abde57b601564aa0320c3f737950a602d9a82ac0d16dc172a95575438537558e7aaab4381acc0e896c182d93a063ed140b948d82d427323de374502b2d55590850d6ce63748d7884399330188350841666062d4e1129e0f33b25a9ba20483184c61130d6230c5c5d96b625af255071b4611e91c00350d61094cc6c5970982810332210380400a30aa86554139c6b30e068f546f938c4e818fb410cf760b5f081992865c8610de1478a10e95dd973a95555e200880d619061399d64b2e130520664420700801461550ca9c4c71ac703475b824b12cd76c30b17975038556807eb063098c261ce3818b8bb2d6c42bc9541cd68e63748d7002203586784e67bcb2e130520664420700814a30aa8654e2738d661868aee0b954aabaa1c29baac73d555a1af0b37476c61b394a39c21dbb497484010029fad81a3e51b859e393a063ed04338360b5f0876ed473c6c850d0181058c828072678526092a41dd1b809aacf81250c91c0c41a86026443870e814c30a4cea2d96e902702358d63fe7e43090c062e70cb062a5324c0c51b0b847d98e2bcc5efae134d297144ea980cb0e600850e2a1cc73671cbff0022e6cd1795aaab753fce046b1cbffd9af76d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d9176d6d91fffc4002d1001000101060504020301010100000000011100102131415161207181a1f091b1c1d130f15060e14070b0ffda0008010100013f21ff00ead3ba83707ad20203b9eed3dc6d925339e28b4880b226d1924b9af6a4ae9e91f1505c3bbc87ff004091615f81ceed583fd991c829a6e2e1e958c1d71bde87ca450a67719f8a8dbc34fbe289aa67043d2b2b2a711dca7194ff00cfb8457df50a68a41f402a6a5a383a1c0c25f0315a482718b0af849fdd4d0b662e79d69981f47fe7c0d1bef4171f34846247865164ed65c51d6adea41d1b22d579ab0f9a1f0d333cebe507fca7c00cd3076a941600c0e87f50ba27c1cad368f93742fa63414022623fc84ba3b1b87ad261318374f5fe6e377e0aa77de02c03b957a7f948a266f94ffb612f25ee0549c2e37c2c582c7ce4c51a87418431f8fea0fd2a3bc2c076b0c0351334c1fe3e4a6029a19f6a0c09a051a9684a655c59ac3fcd0cf5edf7f9a83c9d50bbe2cbf21086a6741a498d19a28e3369375292cce46c592bf9f342ee2a30c27ee9476f01cdff003fa8794d56797bbf8fefdeefe749bee7ef41b20b533d5eb83601efd6be7ed02523239ba29206ee75a0ac86907a14ec312fc7f50f29aacf2f77f1fdfbddfce91db240b2c9e6f4eecfce3da2cdc46281193f429d696beaff0055f29aacf2f77f1fdfbddfcd902582f6a6fa1137fa53005c33dfd54b71937b1436cabfbafdab13faaf94d56797bbf8fefdeefe689836d68d4e33e0df7ac0d25c3ddd0a435e9068591921798bb5fa5428a30744aef1ed589f9030b6caf2c5458e602068b8873cf9a8774bbe6847434a1a382f90a22904264fe28349e475e9470e7c80f56b12e745151172fba987432ef4787d3ba27c9c27e32252ff00035a08738087e686c5cd3e6a1de25ad00f2b41de8415cbd8d5c9104213f1104cbfc0d6821d2087e686c5cd2a1de25ad01d2d077a0115cbd8d5cb1042138a0c9176aa48b3933da973958bf3ba74abacec04ad1496f3fa68439ba0ed50ef12d68bf6e7cd3672c91aa18558bd75696f94d56797bbf01f21edcf23168f1ce044d1d1eb4f9a8a740be6887afa3de8400797f8a64a0b91cb8ef1d5e7867342151deb97d292ccf4986a35af7a85c7372a04fb597d68b27755f8a588f039d12ee09bde840df2f06b1eac1c27e2b922004ad202b97b1a00e7283b543bc4b5a1b1724a09728087e28884bfd0d2bbf7bbf8f2362680256a758092b9d6b64623f83c8041df6abdcd791f6a9dd81c5fb1a7b072895b0b1f884d41021b9709caa64a454bfeaa24d7326254c0b94b11d1de8c6e5ec0a3c84e8fa9534470fdf1aa5c387bbb518f888bd17cf1e58261d8ce9cf960e4351e36c4e804ab411b17c52ed44840b80cb89e820653af5891b76bb63a3f81e20414ab58e3218fee6820830e25e41177fd85746c01f32e37a81052ad630c863fb9a0020b8e27e41177fd8574f801fbdb84b0b226c4121385ca68744a5d6f72d0e2106d22afe2659726cf29aacf2f77128168a063e36f2795af3e33ed1dc6ff00b54609c4f075388ae17873d6c06a5bca48acb2b59b775e2746766e5b588cf765cece8fe0b893603e6547c062effa0e24921c28b923c32dcb27f8f20cb466fcba73b1adb3d61c77bc7737637a8b72456e0dd69e6e0303e83863d064e5fbab870bee2b0c5c6c5f873a432d4c4fb31a42d2c41835d95ad434d53e6009730bc4abe0866d011029d5d8dad9635926574c6a2016a0f7350807583f345f64bd0da2017a8ef7f55301b09f3c52aa09f1536ef57a2a5873f19de950235bdf3405670bdf7b4f78a11f7a9b2f3933e24005570145cdbf4e5d1beb6e8a9e77bc8a846f109de8482eb0be6804dfc26dc8205cd6a6f57c085f91adc210055b80a1e6df272e8df5b7bc25b91512dea33bd0405d617cd324dfc26dd761735a9bd5e02642ed6e0eff0084751ff08a500bac0f9a8a6e629d9a847342f1d28bd042e61d5d0a44495bd6cf29aacf2f770b11780c568500fcad9b5bb1be2bde98d42b77087768106eb03f350679a01b6f676218ea151306c7219270f8fd5e07240bd56028fa16513d70a3456bfb54ccfc0fdf850c9261615a1427c9bd488710ab95a6fcbce6b3889735ab6b88bf8452905d607cd42b7894ed5ac279e1ccfe4080113d56303d6282e54dc39ac2a7566233526b733dde2052f221f75bc58e01ad33547777d1753bc47a7a1434739bef51641c8564b0c4d37c4310c1d46892a0c899511685cf519083c3c76a28a29e4d23e67c2fff00141233d936719e27d26467f0f5b48aee3874fb55f1605256dbce8c2425031ba58f867432498580b2f43c2efbe10bf1131c325f36b4203070757ea9817cd61cb4e0c3d5216794fc50de2819d8430ff24eb85220425c9c2135bc073ff1f56eb843bfec6b1e9c2cda30c9586144affb8a216721725812902b9baf570c8ade039ff8fab72842d7fdad62e7059b4bb0ac183e77fdc51c4092b92c294855cdd7ab83bfb41e25217056141f1bbea29bf1b45306383ca6ab3cbddc331b9de6b9fc3d6dc0d8b1c7a1bd5e33e5e56d24a8609952146bc7ed14048289133b01ae74b1d43a63ebc3e3f56def1d8b437a22516f9775d5e0c2bcd4cbbfe9436a186760cc06b66993d3db88aeaa1730cac01b3c802b0d1a8ddf514b2df6e1d7458ad18d777d2ff1e4133487dfe4a5ba6ebd3b1f351bbc3e15140c5f2f0a7a3019b5092d18ed9d83c22a72ccbd869d386db18f42904b9575df1abee1ba7de84f25c022393e684c2f27d092a7e8c5539720a9917ad341791b57faa1ed46eddc9772b4e31ce4fce5a072007a590b1492639efc71de3966c743a5925202d46b1cfe7b0f0199817c6ed162ae1c7b370fba4eb20337884decc5e57da37719d6c7bcf09dd2f741b25492c1bebd29502a558af1299f323d4f95a2063bc8e018585de0d93afb26bd14cd54655cf8af8dbe9fa3d6d3071de45bdfda0b831c1d0e8fc3e53559e5eee010e5202b0b905bb9beb662ac9b4e745965c78a536733eae8b09b108473ad3153b8768e0f1fab6df13ae1d57e38b14713754ea7b5892438510886f5af3b3c062e7df0b48c7890e8711b02a4311a900613bebd7f8e2107d57b1494322a08865ea1729eeb0beae3de7865222901d33a6010124eabf1642abe7cc5fd534170bf7efc047189d727d74abec9c00c45df548f706f3b1ce8b0f38f2323e7f093448bea1b51333ca67fa9e3415807d31f7b44c20ec51f16c47897aa830021632cc35bdcf63bf1b392c4d1a203ef0b02ef1beecfdf049448ff0056d00371bba8f68e361203d26b01b2792586618cfaad4d848ff56db888000f6afd1feabf47faafd1feabf47faafd1feabf47faa313768e274b44b311f55aefec311b2f90533929e72fe1f29aacf2f77010e92674bfe2d494b83dc7bbc6a0446beeed45e5d6421fad93e383c7ead9bf8fa0a40e5c9dde242a1ef16c4de11f1c06f1798eaff2cc5542e94844a5f578d16de48ea1f3fc710656e5d09fe5364efe867e6a100f3bdbd7e28d08be367bab15a705984c80256ae5c0bf57c654fd1723c5685249a630190584a327e85a03c89ea0f03681ef9fba9d4ac9aa9ab874078ae919bf92093ba1eae3cb630a9df631c22237987c0b657fb1f813f1716c17de2f7019f1efb65e0ae7e09b9ab24635db67fcdfcb326ce8dad77f609598fe29e53559e5eee085eeb6e9ea3f009bb85e960bfdfc0f1fab678fd5c7dfdbe7b5e0479ec266dc7abf04ee35fbbf8ee14d47ca0f37a6bf423d753d289aa0c89950c91b87ddd1a023bc200e95e6477a6faa18bbd021ca6b9cda61219183a05a2cf00eec7bfbd4ae9101e878134203c74a4433c6f306349a8e512bf8bcb696a43c7405efbe373090de45ef62d0019f669f9b76e7f434380892c520c7ddcfa4e362094c056ac23ce2fb3caddc1e3f5b5aeb8d372e7d8e319a465bb478113d08b0b3457aad4c2c0b5aba2a0104fc2a28a28a28a299507b09ead41c08818695bdff00e40cf29aacf2f77003cc10f55df36baa30bad8f71e3106fe8937d0000c0b3bcf03c7ead9e3f571f7f6f9ed780093781e8d82c64df528e98596e3c7765795ddb8f77d2d01ef02e5fc599c2e43268d88728d24acb39afd598456973e9582ed3ea818d24ddf4e1c71cbfd414122ff008734615b463cb2b50bc784739a169ef1fe0fc7e5b4b611a6018ff87df1c484627d5d56287052ba14e9e49cb2edc0084baa6e7b45860cc73665d70a6c0a84c478afc398ba07cdbe56ee0f1fadad7e17e57552228909c4af2ecfd4e9682d97e8dcf79e00bdc039573663b803467e34a4861c7f277ff9030a0fed6c623019eab81068326e56046b96cbd029831cd3e78ee1d21073d7e1eb6f79e078fd5b3c7eae3efedf3daf0090c4ef6b9ef168bcbb1f53af102a012b4970be95d16028c23ad71de9bd97f8c3d7eae4c0d472a118cd894715af71bcb4a4d1465770e14239e8d406e813b51e16e49dda93ac70cb598b87a5d61702fe49a1bd468615a0655a2c668647e3f2da5a822248d3f34d5be636a452084c9e03ac6fb97d2509414019161e0530751eb87af08c69049e87ae1e96823f261feeae5cf8e13811a0b76c76d4d0fa1406bbbbdbe56ee0f1faf0203ffa2eea56210084e0c439d443b7ed43e87032b321e0fbba52cb2e3c031dbdf7acebf16b88f5975ddb52b1084213808eaa0bf919d723f77da2af6c9638e8efc3dffe40c049c5c6c3ee6c73b3d28bca44041723c038934999e0fadb74e3531bfd299282e472e018b2f95dc9d0de885be4016f79e078fd5b3c7eae3efedf3daf0b20776faf5b07d0e4674a325e025dbf6e056a1004ad300a757cfbad39bdae7d074f9fe38085cf373a210d365732a6a23917bd2a5959cc2697fa9a7e7aa955fbbb095773b64a9abaf44a4c1c190b4ba925beebae4299d4a4ad06466b29aae557102b9055f8d249c56afe4f2da70b494bccceae46e85f4a15c2d0cf9a1401deff0096dc102ecfd22a50097190c838520a44bc4a2c25e27d26fadbb619807269020e887e2b189c87cd1e479b93d30a24205c065c1e56ee0f1faf0ece68b9ea52a4bd17d68379f21f3524019fecc280082e2c03c294a9d2f3033e16b4d8188d1860beb2d3f668b8f529d2168beb58d3e43e68e0bcd93d30a00000300b1468fcea787bff00c81850114b6f08eb6df973be26b1ad2f704b7972a461418472b19ec10c9a331dd755af2b67d2f3ad3440d0fe947e8c67cd4689cd97d70f79e078fd5b3c7eae3efedf3daf0a6f7c02ce8f70a47e2c4121bca9204cff0066149b8f98f9a1497a2fbd6f6eabdf5b53209caddb53a97918aff2028c8c346c13a0a823ebaf6d70aef8ea955bd9b07a5f2dd60ec560713d0fc51ad2786a62d0df0186ad0a9890c0bba1f97cb696cc26d77b0e26bc6e1932fbfa0a6f752ec13b71b32492bc6b03351ddf5bb71b9c9b3b019d2f7128994bd2c4459fd9c1e3f5b6e5340eeb9373f1c6fc9a5bf9256725b970ddd5e3646d214a7e1e4c7832e35e4d2dfc92afca7072dddf8590e760c19f23f10a846133a12a442e4d1f3c10502346ac6d6a7e8068ed4cd40bc7ece35d353cf5dcea24201f596221ce7c0c079cb6212dc1ee38fbca1924c2c63b1ef3c53bbd6fdc773468f9351dd38d4cc0c1f06d4d0da55fcc23669f45a40180dccfb9af2366bc3dcfcde5b4b5b6362425ea3483350f5ce8c6db94f52bdf6c6bdaf868676afced2415e5d29a344a257f10973b7a39389410727f78faa248f397c8a366172d00c3d256810f988c29eb1e6cd2876e4c9bf88ed5273e71f4a5be8e631d57078fd6dba8630f650c31e65ce542479704af82c7bd013e9b413b215a104bfacc2b1e3a2cfe12e6ea1843ceb9ca848f0e09417b63de809f4da09d90ad0803cc30ac7ae8b3c57f4145ccd4dca207225203a34be76089a3f1a2e5954254c4a2e831e7f4a30cf657bdeae4357b97d2b39351a323810950bc4caa10cf541f1ad0c9e9e73d4af70b1f7af6fc1a05db2feed0e8acff00a4abdc1cbcad460674803a34a1ac48d3549aae44c0e09ed456085da95e2e8d4b7021e0d06b3f80d80870c37c9a4e42de285d14b70e81c7839d16285027eb30a08d90a77a027d34a0bdf1ed4c8f0e0b420af9d7b9d1bd971fe662da3bef5157e6b21933a799b357784988df4a429c8fcbe5b4b7cbeaff00bbc7eb6f66f67fe26a5a891326a5dcf7a948c497a69a9e4104ecd0501f88c0ababe8f05927e4f2da5be5f57fdde3f5b7b37b3ff147364f86af430a530bcabf7bfaa8d8e46b250fe30f343f2796d2df2fabfeef1fadbd9bd9ff008a4c64f307d1dabf47fbafd1fee84a5ba43ee815c6a79bf57f2796d2df2fabfeef1fadbd9bd9ff000f260d78c7f2e15048ddf9196089314e215dc1b9e3ced48cfc8fe86749655a11342208c8d1e39a57daa54215f5bff7188953c868e29640943f564200ac6ddbcb81ff0009ff0012846b2eaac97273fe13b97b1605f25cdde9c4ce65036a995c268b5c71ed617f965d9fdbfa205652209ca690325308e87f01e307086b63f809ce31ff0088b1468cbcd2033a8120b9897fad0c40d85c29c705c493faabb4249063f7438c3c366b246ad2a8eea852219ef3852291212c7c68deddee853d76110e404eb49e0ab028493ad14bb4b752d2c8cea853a37d849994802b46382fed43236a42b743e270a776234e9bc9c55fddab9ebe183bd1ae800e743083604e651ebcc53d3950b8a6b851b2c87b851c2be627f549449dc47e4af304f2c58efa37b77ba14a970e179d03a14ef70335f504a9b8c3dca89efe4daeb2e50a14c2e6cd350b8bafdd92d9f16937c1f9169e81260501c8041d6b1226f5d0d69e0d335f4bc54e118ee50547cb9437efee79522449a57b77ba1406580ea50ce3c2c1ad32102f8ce8bd829f3f03cea5629aa1492017e85977d9c48c8d5e20670d89a5f3e52f1517319eed3b9ccc97573a0343fb9162462864586245e9475a4792afde91843f25108336a3c3447d6b0ce38376c9597456a3582821eaabe654de97d319bb0161f5cb2aed3bcf5bebc16b6677f5787a5dec3237812b08b28a46cc9e787cd6ad21b0588dfd75541d82d1300622bf7ad28aaa3ddf813d6383b87b94a4e61b058d754d3395d4a1257acb5fbd69016651d2afd3bcf4be9a2e5961d1c7455d377a75a0c7b62f57ef5a8f39e18a7cab1e9fed2a95656c2072967b5d6789b3673c34eb952ebde8d03479f0fba5555bd6bbafb95e435aba096672bbfda51559776bf7ad5d3665a15ee3da9b0c9b864017c97a0ad0722adc57ef5a5201806b1c5115d9fdad5ebc65a57ef5a01109376af10590ceeff3fb9162efb63bbfb95df7debc96b58d43e322a41596ecc6c6185f139ac373ae69ee584b26f6203018f375aef8f65782d6ccefebcdddb3dc7b59e8e45e5a4d4b2160326c750391a6b5e735aed78d94554a977a840c53e6b4d1cf2684e8094d41508e55dc7dcaf2dad815dcdedeb693a4c0a3cfb802d4080c52b47e679510ba6564decbe60b166eb5dc1ed47c19b36515596f6990884d1c30983934292f80505a8dec3bfd59e26cd89425c941028f459d5c04586f6775f72bc86b5770897d28c3930650d0457c016a2c0e295ee3dabb85ae4659839cd818d8f453dc5d962b299e398e358e357394e3bfdd320542362cc1b7a397d5022f88ad74f61a9851510b0fee51e64bf94cd85286412c30fab0dd037431596f29abbf98a146ad3082d8a262e38153a6f55df54ea0748c8e6d03a94085a400557015397cf9557fd7ff004a8a707659d77c7b2ae7e62851a34c20b4a074a07017259b01e87115a9a11641d75c2b017aee3e2a0e1283c055c244d0e062b864a0c90cc8f66816779ff94c9152ae746e82b80c55cbc48d880889789501acd9d2c96e6c1ed6f880b18f46e557fd7bf4a8834c9f1a8320787013542f12900319d391f66c7c52577cec0730619c31fbb5d177b09c28ceca4ad812d3280d5d644d0a24a82f1299018ce944eb363e2b3090685687115b1a0d2d540621ce9e11470592987a372a8de76fd29d0861ac9ab81e8d09a9860b12b9e65090d6647cd228273a44495bd7fbfa190f068308e11cff00ca6ef71493448f177a6a25e5ff00a28aa543b5689f3d24895d5ffeb3e924924924924924924924924924924924924924924924924924924924924924924924924924924924924924924924924924924924924924924924924924924924924924924924924924925fffc4002d1001000102030802020203010100000000011100213141511061718191a1f0f120b130c150e14060d170b0ffda0008010100013f10ff00ead30912c7551db912d38278d83987d56804fe8094a4c889dc98fba221933784653aa55d9d61d90b440a6178e6d0f2f203a06cf0c7ff004008def1e39669a4c5e189d1dc3016e18bb8289a2317b6f04bd628e0cda90e521a78e451dd65faad32b149cc49d2a4babcfdc54e5fa38b4453d269c04a0180e09926b88d11c59662b69de60f0dff00f9f23c1417053de71dc35081096a5bc5a034b91ca3a34065c7173f811ad872a600a28a1209dd5209e14ac0f1141841cbea432b36b6a5627d5964b94c7fe7d89929cf06ea2e74a8638db37a5e1dc762198c9a000955c80bad465e07c4cdc04c1dd6d85083aee92e8a9715647111cd9101ceb3078143f7ebcf06305153522e0b80647dff00a83aef94bb7055f690b51c0bd94a1190108e89fc818dac58ae8ab4f3a13338404d0c0f5fe6c0b2ba3c6b4afe60274b7b1330649acda22a518d04760a7a02b0211cc1eb14fd05752f4d1e235e9b495d015d5c278c3408f4980d0832995c67fd41057b225405c854bac9a6c1d376c069e2a48f03f8f3792a31c546f8346bce3400f31a5ac326799a264e548e610f2203cc8fe6af8409a0a1f1a538244d3591f61b04550c64367324a21a39270adf7e4d48f52d8bc84cf8561949a0c4be1ad060873b6e934f2ca4b01b258978285eb9ea000fb7fa95befdfc9427c06bfcd317313f18343b592f782bf6f5da82e2abd904975226e7352378584eeac67ceb0a4971e3ff003a60b3a4aef14f141031efb8bc24a49c329600c0190181fea56fbf7f2509f01aff00344c1c04244177dd68264601c1247475ecdc65d4d0080581a7f552664eb9a8bf7feaf6fbf7f2509f01aff30840a30012ad21f4202c5887ae9d5a51742657f4466fee9d4baf9a67640d88ddca11cf6542037bfeaf6fbf7f2509f01aff002ebeb20d2bbdd0dedaacef1044bfdbd1ad3a148ae8e8bfd8e54e0dac61900c834d91ae00a23c10bdf1845b1d28340250639a3cc6ac1f29577efe3054015700ab9b1c901ae239c1bebc3df2e5ef43f8aa545970cd6a2165903e483de8173ca0c1aaafd269ca2a108474fc4ca4985e0e0b7e43595f5307517a146921aca744a7fc44ad34f0ac2722fdd4d879b6c6e161e4d328f94b378fe3d589a50d737215ff0073dc4b27b54116ebf5829f7140b4d3eaa3d213dea7428c709c6c3c95dd489625e8d11b9f8b51729435cdc8359e5bf56593daa28a75fac4a7dc582d17e9547a427bd4ce518e138d8793ca9c3c4bd1a2373e407a68c80416335500df4122384edd6e0391439cc8de300cd242f724c72448528468063570834198e1c39a53366587a627bd1ee289690638b48f3518287bd08bb31a391996e794fc6df7efc10bdbd24235fd246faca344fedbde8ea3bcc7d29f5cf37a8cd95648f213deae44f5284deaef334af2e0e15a265f354402148717a8066a1563974c4d41014b097a7acc6790cc70c668b6402c3aa586f5a6433762db976e83504471b872815649de4f493a58281c8b5721a456374bdc294dcc37ca3f8912c4b91a0176890e0c729c2c1cd9dd5abd045b909ef47ac99a5a4104ebf78b5ff73d4c923bd2c6cd501ae6e63e027c06bfc6021228439018d48288047ba01077294ab2e0c09844fc17ac04060fde81fdd58539109b7b91b96a34b525856ef51bf0a58dc9b0deec8bd184f753da6af588910a163ba628846c0479c418ee665cdf250c4f0864d1092632e2e38a3282fbaf4fe90e2c0ee588e33465cd9903964a746ad375cb8e81cc484e3f361f31820cd30066b58ed7262e89f6bf0f9c271cdbd681837e276abc89c2f96cf0fcc1cd9821600316989e07df43d01be85e8c1c03403e485880223766373251309421374763a4fe03859951600559d89f75bcc38d637e3400000800803e43de3041b29fa1ed51712923c16899ab9c2ff31259951600558de9f77bcc38d637e340840200200f917750106ca5ec3daa1b25a4782d066b0e17f8c785d92e8d8c3929c8c40a6e6f47d0b025f3fd239cfc8916d92227de6350f41bcd213dbe16fbf7ca83520a577ba062ad8ab0ed8b8742e235728a2c5be4085d91272065e37d1a8627986adccdd899fc9a8094cc0b93b1d363ba0398a85d9a23ad8177a98adefc8a3b101235fd0da8282959b7264e9d3f00e97048f15aba0bbdea3ee4116ce3e83bfc81005108964a686fb71414dea6d674cf6f80d7f8b0960bb479476655c0e88c5e5c6a01801b19dc77f9af4284175df5d0cf2120f52100c518aeae357f582c8f77d38b9e9f12423ab602c709d78d2cb598a01f4e8e5c2bc7edf04e7a0e4d184aec53c16ddba2b0af712f36902f377eaa3fe8df82f0c8ce0a6bb0889c28e61313bb4f919ba1f746d409128775da3df176cae02792032ba5333eca8f73b5156ac58ce92a3845a54c7d2768f72472d7ef5999f18a65abe59d0398908e8fc4bb6a3750f98c598d663a0c6bb5601e5a5b8315dc50c4831777059f546d5dc041ca8c27e02aba181e4bb634da6b9a0724c46911098d04d9ee609cf04f936a500955c00a9a8aa019fd872c31d9b80913b83c8a6715523f7a3da93136e60f229920f4cbc0c5ca7684c34870ef90a399b6842f01f49923f15c0c012ab801529444133f719b9618ec99298203dc3c8a5b13622f2963da95936f69e4532dce9937042794ed3d82b7266c850ec9d941780fa4c91f8799d3e26d7c69837260e7141c9b67279343a65fee21ed5abe401c6509cca4a633adac47818deec453785a8cd717e16fbf7c6205b7ca9800e3421c6e34719f466e6df48d858624caf08cae4537387f590f6a46e318f3a4a90529333d99794ed1645c11a4fd86655b9150c473425ff0e6291f0606aae15653eb746938e85a4413e73a50d1a42e67415dd68010512260ec9558962392641b8d368ac340ad9e2609a9f20c337923c5fa0cd4287e92e9bfbe6bfd6dce13a60dc928e714189b6bcf268466a847e74d7b56bb482bdc1ccf8f80d7f8b823e412d292715c934058030eb2e42ebb8a983a965152f35a49d9227018763e564e4099b81bd8073ca8d1205540b14e6bae2ad63b06f0879065c6ad217163a87a8f228f35d74fa742adeb22ccea45418d4c86778dce4f2a6ab7316ea07d99d09d1250a30475a2e438304c9f13c6a08d6c10d2ccd1c4ed4dd9c1944d75e5135640c635e868323acd00f12958e0aebab9607cc3d2a018362deb66e356d667443033b2d4cfa35a4b1f2f66aadddac5c2508d44c2835b8b22601acea339c400414489826c02d0902ece7bd33c1f8979da02eba810ae06bb66a53c6461aae9ccdb164579a068301b8da30c98d2bf68a10d5b10d5ca2802ad7903fbdd96c867a51884cda0bb88e54da1ea0844c4f88de304b3123cb7e2e8dad1468c60308ece067a52c8c95570343716da80888c89953b1e86cbc99c4b46fa6945e472c51223b02288c590ed0b6f0f8c2eb04b3123cb0f1769683593818ece067a533449557034371b5285225c4ca989242b0f51c4346fa6942093285608ec0c2331643b43a87c3cce9b4cd4b0c162ad0960aecfd5714d0beba5252a55bab9ed76596a314aacacae7f1b7dfbe31360c42d6f6861c746d6982229991e08cd6c6f7059df2966f5daa646510ad46a25c7775ca5dcc4df457d49481c11d360c25cc2f8dbec8ffafc19894832ba302cd6456090850325fdd6d03e0c6eb653d570ec776344e30bd83f4e4993b0e7848581dd13c47c8fd8a42e89e50df7aec5d235a044aad157aaedbd6716d0beba52229559573dab302555c1d4dcdaad8b6300720ece0e5a7c3c06bfc5a90bf9a00af0caa4605d59b7835a49e127234a000420ceb3f8e313331182a5495890b9778181b8a67d68bb1e2ef196b8e94576c83542dc177919d36a8cb2d6dfb1c02afbe2fe61bf19e9c28045b227a48a65ea47a9c322372234a97460c4b05d69a9994e43450463a544250f8517194034bed3312834337750f80b4f658cdce8d3836d1a030efc785376ff00289226d401dda18505f3844f15bf3d8c9a51a0362d1661cdcbe6904476644712d0e09a6c0bca1e0084e8d4bede2e4407990f3f85f201c5d60cb845b23d39e3005d6e1a69f27e56655f93f271bdb203430f18dfb6d908c2099c4e5eaf8c7731b17718e131c03662c8daf0cbc7412f1833a795e7650caae6cfc960816e05c37248de3aedb2a74300201b854e5f03f466c5dc63848700d88223bbfc761dd2956996953755cdf94d7ad77be4ee136d4dfb44ac7860600dc2a72dbe674da4de253c5ba6e10a6a9a7e3b7dfbe120c30d8aac050b20b868cf392f3d846cd83712d6f240dece54888a99573f92d0653953fb126e9d363b16192048469215267cd5deaf9b32f65532d9b70c1c1d7e5373196c0243702f1ded8080288473ab4606d3f427c21df40c9098f180e74104186c68205b8974dc10a6a9a7c80df998032239334539089992d1d05f8c996df01aff0016618997cab70e41ce882336ebfa23ce8f67234592f87083e3368d9f7964ef2c6e974a61bf64be16e17073d363d004ed511d8151ed4644605f15740f8264b90de096f026894308672bc019a35270145be69c6b2f232a082b4a61ddf7addb835fc269928770f70da91b48cac18ea73f9bcdb80c712e52e5b79ca816e3d5ed5ba077307ee8eb800dc107d6c7b32c982c89e1f34dd451c4191ea546ec2c1920a7264d96dae9dbad7c071320d411ed4000160d923112fb170f1c3e6c295dbc1fd576c9d427dec2382b3565fa1b4e2641a823da80002c1b2695270cb2b0abb6e87cf1e3c78f1e30d50eb282759b6020b4d5976bcce9b1df2750bf548e95dbc2fdfe3b7dfbe14a910352fed4f221d3610fb4397cdcb597cd3079a4a402a46e26c33902dbd5fa7f2cc6d445df78fea9d924cdd495eafc9cb48e68193a4ed02413fbd07e049f90b4657b77eceda15857ea9c227dd51fdfcefa5c4ad89748f3dbe035fe2f2524f07f6e84033277d692d9c4b00879411bf7aa4d1566258a68c17c9e74db1a20b99a9bcb6c4c910da37050cd3c9f2aca791b9cf4a14216f2158fc02872491847d11ff5d8cc8ce6e45faa02138c0fde7c013f0a6f029636cecce1286b62f9562367b24ba06862f7a703d5654c5fc205d2e31d5b5333924b5f9891340e334926309e31b096a25976e50e34dc36cddc405c10fdcfe04d65322fa5aed1b01cd8839ffc3e0037093a0daddf4c04e96fb7e06d4b1d3c1b0c9d7d1b6072dfaff96d42cd66a75dbe674d8506044f37e4b7dfbe11594eedadadaeb9d5f331b634da9c965ac67619994ba2ff00bf9734cec3f7f39c64bd7b0db942db5fc1217737f85fdac58d3eff00c5849a37610ff8a525da0b170105c65cc2856992851823ad11982d9a73ee60eea29512e31aaff8d298d4cc5ae9651d0c2c2e37e2ead018e24aaeeedc897853011b5b7403edcf685d96cce321c28444589725db54789f0508bcdde84fb50d00c564920c16e634a4a0d90e6bf8bca6bdab36b68b93e0a397cf0af0cc13ed36836166c9bbb683b097c87f54dfc8db5124d8d0b032d23c5a7cd2f140c55c0ab59cd5025d67677ff9c00c11339138ba753e6f34c8cd00eed5ff0054b700fad80e643b4843688d178e4027b6d77c672830dc22776f2bcebf55e75faaf3afd579d7eabcebf55e75faaf3afd579d7ea88b73115be00a250419902a0d5baae6bb7cce9b1e3b57e4b7dfbe113e96ae85db3451398c631f34594a98c9097225e54658040191f9d2cd33b0fdfce741a0335b6fd3676ed1107ee9f5b599207b9f3217f2401c5c6e736d67e52ea94768fe2c311528419139d106809965d8d1c4e9953a468c9571635bd30c236421aa31fa3b51dc7bbd68d1431776addbe2a0ce2681acbc4fd34374287a92eb14edaa28730ddcc8dabae4645d981e6ab21a948c9ac7181e7f8fca6bdae9110a50caf3cbc1f99913e4dc9f7041b875d86bdb76004ab531f04dca767201cbe19a737b99ce3273d98e20ec89c5a05e69ca91d7c9803089acfc9c9a590b6316b18b81aedeff00f3802f970cc6cb5c186f0a6648844847e4a546615a2c1a85ea9a6d33a4b18370f3939ed2d45d17f7731de62f3d8865b0b18ad6f404de35a44040c2397e4f33a6c78ed5f890b21389b128bb003f71f07f05264923d4a4f04b8664b9c99396c56808695dcb548873d7e604820989b1eec471ff000096699d87efe7387db4ec00b9dc25cb6b94588d68b16e0751d4f933244004ab4a53f0dc44768df7aec0dc0a4c4da7a8a4a24ab2bfc63a91a6b8577e8e4d08b0c24e97fdac1a635ac82fc17ef385022b679df70eb490df684e142d23844750a4920fe8297b53f9bcc270feaa27498ed1bdce9921474c1ccedb0e4d982ddc964658b584cd864a01aad8355ab1baf430837001cbf1f94d7b4990211244a4542892f9a3fe832930728a84211d3682a012b8052e670a745263dd72d682a40f800800c8d8923390af997638e8f887b686401fa3ab6a1266550667396fe0e7ad354690a378fc0f712c11cc5ea6067a541ec43159a669baedefff0082002c261403cf496667c719c7d8c8d11b9f02a85204f45c7b0df85198ce0e0fb735cdd82682665e45f845f8c19d2222a655cfe17a836ac03b204dfbdb426261c066650b3cd95f171f25c8d11b9f03504f615bbb037e159e60ac5dac7e8a5e98b318c4b212719e9f0f33a6c78ed5f88402e12b844ecd905309efe0e691ce9bd2a04226227c0a109298970e0d86fd1b5d25c8571c47335e8d295e5a1c28c91c3e0bd88229f418a7f4d4230b1a044007f8096699d87efe738511144b8946310255d25b845f8c996c73191ec1fa731c9a7928c29e8984723bb0f84e2ece468063512635c079652723038e1b23a00aacb3b655de34fe3b3388961a0c13735835c3c40c5394d163c5cfd4afda94c4a24dce269097961fba2d2478a5ab8a0cc5d62adb89876f5a1c21eb1e763bd13fb4611c809baefc9d289f0b133edd68e534f890662ab2b4640cb83e18a0e85e332901375c0faef5ead04480960f2b23439b7c3f1f94d7f1c470cd3f14b730d2059b6841c46869d66979aaaf5b11217526dc81b42f8701ddb7ceb917ac1d00b67d107fdcfe2a1184211331a8491a488bd073cedb0eb8087393613ad27e180473ba805cc802eabab8d34be758c750d09d18380681f0efff00860200748e8c5ee4da9df66c30e64a82ba78975555a205210752deca24201001006cfaf8ee00cd700ce87c937b24d9ef62bcb00f89878a302644751a1eed3023847a39993cb6da3d201ca44f26d49f236187325442e8a25d55513234853a843a86862340200d03602e06f9182dd11dbe3e674d8f1dabf15f9d6ac124a79ced1156ca5af2646a46f57c3b085c4a2ee929ae88285188993b0fed1eb8798539c793efa2d5e23cb2daba3489fac31e734b8ab247798d271a515eaafaa3257a449d41b391458b7f8296699d87eff04e30210ac12fd311d772d46f57f1354649826c642210892255c205611756fec292ba78975051becdc63cd95461691d5477e45b6bd22934e98f46466db58575a7ca9955d57f9034a0c1184ae0a00fa2d468837abf546a5d78a229059dc679f74e964e6b3b05f91aaedc1c83ad2f44a80e3dcbb83073a1b03d325839834d03842082381305147f4f4387db2fe5f29af684f38a2609725a6d733f99e49a216c97ec794d14026b068c6baaddf98a3ac2059951764312373826ab396958fc83e8cf95e69509d10c02f133b6c02524d0dc49f67cc009c1820d01d6be2be93f39f3c38e5a6771c0cda2173ccd1bde867965f393e00fb266398e3446d0290f54e7bd7d27e645e471cb4cee3819b43480aa62b74cd412ee83e37ccb9c6ed866402f3c7f89cb2a40c23ad13152509d8d67464df07e0b66e150045ce279edb28875cc066b4a196859059adc1bb13bfcc984820572b64deb51160703310313cf638ad8e9b951f4fc117c4a81b253c40ae5f3143b847ee8810512267b000450a69f2504a53046f193ae79d47d70990da6571c1c9f9c17e22736a8cf72fac54c710bd8320c830fe61c8143688bea296409af013d8395772fcf21e535ed41e521188260d5ac28465c247313be8f5c62b079bfaa365bb85f78a2e4fe05a6a2fc4c3e179054f9389339b948e4277d4e39c766aaddfc4e61622a869d92e6ea5f1f15fdc28e0f0994ba02a37f09b27d350b0b098f340ef418a6c201bc36735ac51ce941a0601b8b52d38924aac0bc9bc982bad91c29212fb812cf2a4037b31d88192c10641c7e60356205132a61f2a0c83a9d669313c7b15ded4709a49897d2151623727d54588c26bcd83bd5be40207c3039cd62d4075c0d0dc7e1482912e265507b50441a62759a500f1ec577b51e4d24c4be90a8b11b93eaa2c46135e6c1de9d02b0871cc0e7358b181d7034371f236a63abcb26f0b9ccceba515895b934a588084066edd1425d2d79fc663e4e93a894c43d609deb57deb953d7734872050dc1902ec892c37ad4521085902c3b800f8359192856a3462130585b863c8d0829c545d5ee1434f086fa4284531e244d198c988e5e4153378261cdca74078d3fb194b37ad4000c651620c02d2d90d68a548377103f705208113e75c6b7577afc19d386627450de209bca9361c832fe89ab230353313e2c0b844e336f9ca11808a81741133671dd4114c876645ac71528eb2fba422078017ceefcf19a03ae1bcdcd5ba04a0fc7139454fc0c62bc9277a2c91bfec53e5d04c4be92a240f0ee573b548e945006989d229288a99573fe64c44fca0b639a026f1d6a17589705d3598b68f16bbbd4ca461a500c06762b25010cfb9f97ca6bff00cdea009c0b91e841912a2f8c7378a9dda949fd460b472b51842c27eccbd93bd0b4b8908df8943583c61cba01848c988c7f2794d7ff009c5401389084935009d46880038c109266becb419889e85011018a3ca9e501273996e42f2fc9e535ff00e715004ef579110ae37594de35db6edbc072d0e79d26095048acfcd8e47e4f29aff95a80271dee94d9893c72fe5ef0c4233105be589f9064b7061cc0ee738d3e5a6528d93133c7e641034059a286ec57ff00434bc9840205b851774d1360488c89ad2eb0318b90d55b06fa794023451fdff9cb384e62816394d1812ad2a244a6a0d82112aba54c25f0214dce607f83f6bfc287945f02385108886202c9ba16ddcbf868b02b738b2894e04dedf292dcc40ae4b836ddce8891cf81590d8ae96a886f1661b30d5f8a9bb61abaaebf2eeff4ff004bd904b5008e90286e2d53aad369da9113bf1fe01e37b3000ea021ca2ae18a5526e03aa7fc2fb5b2012e8980d165862f5a438bb172325e3434bb66138c99399166a1d002441df36e02a20da843b2c3759a933397ecddb044d825a21c162f7c82936014854d63fba864397b0ba438b48b34e59108e23b20430999e2085d77659d61197c4c6b1fdd5e6f75de280dc7760e5b00c38182224a54edd10966f1ca804488139d0a07008ccc2c5db4105da478573153586fdea3713b7f8a0371dd8396c74a6012ab814e6859b21b8cdc5b51262b45537c10f7a05fa203219a67066373e2f2db2098e86ab48c768c5f9e474229e7001419e0b724a605ec2499d1c81711304c705a1ad3e142560cdc5b51653b4150e047dd304a039df520900c041df36e0bb42d6da001dc3a45354e243396f8cf3d9632837f882175dd819d720f898d63fba9f486647980dc776797c41a05bac33169ccb74083be6c70268e58b8003d8e645083da3de40877db60cc8934de5b4b86c1d716470736b22726c4444b26ee3d6c73a0bde0180e2c76e5440914c2642639b475830c810144405c78672a632bd020ef9b1c09ac0ec4001ec73229dddefec6ff00ba495149c8c2492a65249c019105d776050d21e0c4c70fee9b2e1cf08b99c1395108cc9082d9384637a6de02d0cdb6045bf7410055600cea3b2646f1ba0c5ae4503602c150e1fdd460032d5063137134796c8715b62ca61b9c75a3716e0020621067b1856fff00437fd56272e005ee72229ccbf4093ba2cf06281a14b2c3213fdcbed6cbc4b98d103d9d877576c8a087bbd69ed2b578a9f50149bc0d3b0054dec47ea82d91ed444d1a5540e188bd20d890c1898223ee1e542e40a86a2fde681100073c8ed4f6b444482bcd7b1b18f5dca3651e49f7467011865021debc068ac5f2c6bb0fdd430809645307407aec6f5a666051ea776846880190b31de8021823c028842c12e6203b6cbf8e2770481ea31d2830eab80a5229a5c4a80e00eb5e55faa8490caa46493ee9959f46c93fa07afc43a70409d54076d96bdca2c244ee97b53eb550cab8b857957ea9d215730410b9c4e0d0945cc73830ed4eba132858579aec4250e44c0a3c93bb4458080ca031de973200b36c702bcabf5489394391b4dbad4652e738408ef2a72ca95595763d20758a247418e5b3b1ecc278862c1fdd739d1184b86aee74c39501001138c61dd3c014cda895737644f3daaaecc383c024f3972a66f12ab2bd2bcabf5400624561042531c4e15e0efd2849af2c062f3676f77fa6c048542e6a2778a201889a87962b2d7957ea9ff55137c984e5cea07224191363e1b0cc548a4a59c0d00e15e55faa767122123d286c056ed2ba3987fdcbed7c7a60f2faabc868af01be8069306b0cd236045c80c18dd71d23626359c468c6e319af11a52924865a999d268e29e5e4a20e048e09b18a882216239027674f01a2b17cb1aec3f7b72f077ebbfd40803a066e4c34f61fbc0d7479dca05402568d2284849cab49b578ed543a04dd189e95ee9ff69cb12eaa5692909dd2647962dcd302b843b9a9489064e1c74a714ec59196cdfe1b56c5ac15261b210df109c28f861e5411a559b0b95a568a155c6e6715348a3033c0f65a22620132883812383b20dec0085acf004d786d149132f0e15ee9ff006912a975595a0b651755210de40f269b3a60d9d47329cc5c0e569d7942b0b8ae041bdb3b1ecd4b542264d20804b844beadce35198288c2dc39107376c4f3daa972b4230cd8c6fb0f2a7cd9923a8e652fb985cb4d540cabbccebc1dff00801fb2a5e581f4a6d8d4a05ac3510f668ebf096886ec9c46bdd3fed158f4260bad2a952add5a40085e9c9fa5270009089b269d162e12bc8cbd14e4e6072548a0928f637b30535351219678ff00b900e6e0cdc359d891c22a03749e2fb1ef19481711ed4881258e92cd6f81c44c3314f3582636cf60622ce30ea68d39d12c1cbc5952db1d0900c685b0c28e7b598d8ce9b12804aae452d216d914e9993d2b1dce598b8dd49d13b7e185759c74a430b3668de0b8898662a3aac4c6d9f7a8cc990c50e515c0b2abdf63175b0b0a225bad18f0219cc1c76143e20480d074dc942344a113c4950da5e00117be2db16a57cc44c4ab1dfe0c42a550959579a83c863935a93342dbe00759a48805a54e74d39cc85d19ed52fc913a4b3b1b1308423a94802089c0b8888bd2a0ff002220e809eb4cbd977003203236373532bdb54b2faac033b662e37507ec3b3025dcdbf0a1739b1b280063cbe0ab4c4211332883a45a0bbd112784508aec207404f5a7cc77596e0c8ddb140a8042502fbb68e98428419f073e14c897d72b2ec7eb6c80de19ed534c9b812cc529d51084752833b45a0bc4445e945e25840e809eb4965b29d83429d9ad8585112dd6a3f8144e60e5b61ecb2608608e4d66575810dee02ce17d9be803cd2cb86156a6c760bf1baa536270c3aae6ec0a7668920e22694d0bc64a18b32926cc3cc79868999ba8c536123a063ad6916c8a7000278cd3685a8655717fdfe1a83a2464afa84e5899e84c2830bca2023fef6a76a5c92720c78aa9191a6f5ffd14cb02e2a12838ac623fba9b3de292bcff00fa6bf9fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb579fb57ffffe000300ffd9);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado`
--

CREATE TABLE `estado` (
  `id` int(11) NOT NULL,
  `descripcion` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `estado`
--

INSERT INTO `estado` (`id`, `descripcion`, `created_at`, `updated_at`) VALUES
(1, 'Activo', NULL, NULL),
(2, 'Inactivo', NULL, NULL),
(3, 'Distribuido', '2025-08-06 23:30:50', NULL),
(4, 'Anulado', '2025-08-06 23:30:50', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_factura`
--

CREATE TABLE `estado_factura` (
  `id` int(11) NOT NULL,
  `nombre` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `estado_factura`
--

INSERT INTO `estado_factura` (`id`, `nombre`, `created_at`, `updated_at`) VALUES
(1, 'Activa', '2025-08-09 00:41:11', NULL),
(2, 'Anulada', '2025-08-09 00:41:17', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura`
--

CREATE TABLE `factura` (
  `id` int(11) NOT NULL,
  `cai_id` int(11) NOT NULL,
  `tipo_facturacion_id` int(11) NOT NULL,
  `numero_factura` varchar(45) DEFAULT NULL,
  `numero_secuencia_cai` int(11) DEFAULT NULL,
  `nombre_cliente` varchar(150) DEFAULT NULL,
  `rtn` varchar(45) DEFAULT NULL,
  `sub_total` decimal(60,2) DEFAULT NULL,
  `sub_total_grabado` decimal(60,2) DEFAULT NULL,
  `sub_total_exento` decimal(60,2) DEFAULT NULL,
  `isv` decimal(60,2) DEFAULT NULL,
  `total` decimal(60,2) DEFAULT NULL,
  `credito` decimal(60,2) DEFAULT NULL,
  `dias_credito` int(11) DEFAULT NULL,
  `fecha_emision` date DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `comentario` varchar(250) DEFAULT NULL,
  `porc_descuento` int(11) DEFAULT NULL,
  `monto_descuento` decimal(60,2) DEFAULT NULL,
  `precio_dolar` decimal(60,2) DEFAULT NULL,
  `estado_factura_id` int(11) NOT NULL,
  `users_id` bigint(20) UNSIGNED NOT NULL,
  `factura_imagen` longblob,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `descuentos_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura_has_pago`
--

CREATE TABLE `factura_has_pago` (
  `id` int(11) NOT NULL,
  `tipo_pago_id` int(11) NOT NULL,
  `factura_id` int(11) NOT NULL,
  `total_factura` decimal(60,2) DEFAULT NULL,
  `pago_recibido` decimal(60,2) DEFAULT NULL,
  `cambio` decimal(60,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura_has_producto`
--

CREATE TABLE `factura_has_producto` (
  `factura_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `seccion_id` int(11) NOT NULL,
  `unidad_medida_id` int(11) NOT NULL,
  `indice` int(11) DEFAULT NULL,
  `numero_unidades_resta_inventario` int(11) DEFAULT NULL,
  `unidades_nota_credito_resta_inventario` int(11) DEFAULT NULL,
  `resta_inventario_total` int(11) DEFAULT NULL,
  `precio_unidad` decimal(60,2) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `subtotal` decimal(60,2) DEFAULT NULL,
  `descuento` decimal(16,2) DEFAULT NULL,
  `isv_aplicado` decimal(16,2) DEFAULT NULL,
  `isv` decimal(60,2) DEFAULT NULL,
  `total` decimal(60,2) DEFAULT NULL,
  `idPrecioSeleccionado` varchar(45) DEFAULT NULL,
  `precio_seleccionado` decimal(60,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura_has_stock`
--

CREATE TABLE `factura_has_stock` (
  `id` int(11) NOT NULL,
  `distribucion_stock_id` int(11) NOT NULL,
  `factura_id` int(11) NOT NULL,
  `cantida_factura` int(11) DEFAULT '0',
  `cantidad_stock` int(11) DEFAULT '0',
  `total_disponible` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `update_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gestion_cai`
--

CREATE TABLE `gestion_cai` (
  `id` int(11) NOT NULL,
  `numero_actual` int(11) DEFAULT NULL,
  `numero_base` varchar(45) DEFAULT NULL,
  `serie` int(11) DEFAULT NULL,
  `cantidad_no_utilizada` int(11) DEFAULT NULL,
  `cai_id` int(11) NOT NULL,
  `estado_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gestion_diferencia`
--

CREATE TABLE `gestion_diferencia` (
  `id` int(11) NOT NULL,
  `monto` decimal(16,2) DEFAULT NULL,
  `descripcion` varchar(400) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cierre_de_caja_id` int(11) NOT NULL,
  `users_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `isv`
--

CREATE TABLE `isv` (
  `id` int(11) NOT NULL,
  `cantidad` decimal(16,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `update_at` timestamp NULL DEFAULT NULL,
  `estado_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `isv`
--

INSERT INTO `isv` (`id`, `cantidad`, `created_at`, `update_at`, `estado_id`) VALUES
(1, '15.00', '2025-08-09 13:44:11', '2025-08-09 15:05:56', 1),
(2, '18.00', '2025-08-09 13:44:25', '2025-08-09 15:05:55', 1),
(5, '0.00', '2025-08-10 00:01:41', '2025-08-10 00:01:41', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jornada`
--

CREATE TABLE `jornada` (
  `id` int(11) NOT NULL,
  `tienda_id` int(11) NOT NULL,
  `apertura` int(11) DEFAULT NULL,
  `cierre` int(11) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `comentario` varchar(400) DEFAULT NULL,
  `user_id_apertura` int(11) DEFAULT NULL,
  `user_id_cierre` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `marca`
--

CREATE TABLE `marca` (
  `id` int(11) NOT NULL,
  `nombre` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `txt_comentario` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `route` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orden` int(11) DEFAULT '0',
  `icon` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_id` int(11) NOT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `menu`
--

INSERT INTO `menu` (`id`, `txt_comentario`, `parent_id`, `route`, `orden`, `icon`, `estado_id`, `updated_at`, `created_at`) VALUES
(10, 'Menú', 5, 'menu.index', 1, NULL, 1, '2025-07-23 08:31:58', '2025-07-23 08:30:35'),
(26, 'Usuarios', 5, 'configuracion.usuarios', 2, NULL, 1, '2025-07-24 21:40:38', '2025-07-24 04:33:54'),
(27, 'Roles', 5, 'configuracion.roles', 3, NULL, 1, '2025-07-24 22:22:05', '2025-07-24 22:22:05'),
(54, 'Sucursales', 3, 'GestionDeSucursales.Sucursales', 1, NULL, 1, NULL, '2025-07-29 18:59:51'),
(55, 'Apertura de Jornada', 3, 'GestionDeSucursales.AperturaDeJornada', 1, NULL, 1, NULL, '2025-07-29 19:00:35'),
(56, 'Cierre de Jornada', 3, 'gestion-de-sucursales.cierre-de-jornada', 1, NULL, 2, '2025-08-05 05:08:30', '2025-07-29 19:00:47'),
(58, 'Producto', 2, 'inventario.producto', 1, NULL, 1, NULL, '2025-07-29 19:41:41'),
(59, 'Ventas', 1, 'SalaDeVentas.Ventas', 1, NULL, 1, NULL, '2025-07-29 19:46:18'),
(63, 'Marca', 2, 'inventario.marca', 5, NULL, 1, '2025-08-06 21:04:29', '2025-07-30 03:37:55'),
(64, 'Categoria', 2, 'inventario.categoria', 6, NULL, 1, '2025-08-06 21:04:38', '2025-07-30 22:13:12'),
(67, 'Presentaciones', 2, 'inventario.presentaciones', 7, NULL, 1, '2025-08-06 21:04:44', '2025-08-01 02:49:26'),
(68, 'cai', 9, 'Gestion.Cai', 1, NULL, 1, NULL, '2025-08-03 03:44:21'),
(70, 'Bodegas', 2, 'inventario.bodegas', 4, NULL, 1, '2025-08-06 21:03:49', '2025-08-03 19:05:59'),
(71, 'Cambio de Sucursal', 3, 'GestionDeSucursales.CambioDeSucursal', 2, NULL, 1, NULL, '2025-08-04 18:57:57'),
(72, 'Recibir en Bodega', 2, 'inventario.recibir-en-bodega', 3, NULL, 2, '2025-08-07 18:01:50', '2025-08-04 19:43:24'),
(73, 'Actores', 1, 'SalaDeVentas.Clientes', 2, NULL, 1, NULL, '2025-08-05 04:57:53'),
(74, 'Cierre de Caja', 11, 'Caja.CierreDeCaja', 1, NULL, 1, NULL, '2025-08-05 05:01:56'),
(75, 'Departamento', 3, 'GestionDeSucursales.Departamento', 3, NULL, 1, NULL, '2025-08-05 16:06:38'),
(77, 'Compra de Productos', 2, 'Inventario.CompraDeProductos', 2, NULL, 1, NULL, '2025-08-07 01:22:32'),
(78, 'Entrega de Efectivo', 11, 'Caja.EntregaDeEfectivo', 2, NULL, 1, NULL, '2025-08-07 18:02:23'),
(79, 'Recibido de efectivo', 11, 'Caja.RecibidoDeEfectivo', 3, NULL, 1, NULL, '2025-08-07 18:04:56'),
(80, 'Empresa', 9, 'Gestion.Empresa', 2, NULL, 1, NULL, '2025-08-07 18:05:10'),
(81, 'Lista de Facturas', 1, 'SalaDeVentas.ListaDeFacturas', 1, NULL, 1, NULL, '2025-08-09 00:09:26'),
(82, 'ISV', 2, 'Inventario.Isv', 8, NULL, 1, NULL, '2025-08-09 14:45:24'),
(83, 'Saldo Inicial', 11, 'Caja.SaldoInicial', 1, NULL, 1, NULL, '2025-08-10 07:00:46'),
(84, 'Cierre de Jornada', 3, 'GestionDeSucursales.CierreDeJornada', 4, NULL, 1, NULL, '2025-08-10 14:15:18'),
(85, 'Gestion de Diferencias', 11, 'Caja.GestionDeDiferencias', 6, NULL, 1, NULL, '2025-08-10 17:47:38');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `menu_grupo`
--

CREATE TABLE `menu_grupo` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `menu_grupo`
--

INSERT INTO `menu_grupo` (`id`, `nombre`, `icon`, `created_at`, `updated_at`) VALUES
(1, 'Sala de Ventas', '🛒', '2025-07-28 01:44:41', NULL),
(2, 'Inventario', '📦', '2025-07-28 01:44:47', '2025-08-07 18:01:50'),
(3, 'Gestion de Sucursales', '🏢', '2025-07-28 01:44:53', '2025-08-05 05:08:30'),
(5, 'Configuracion', '⚙️', '2025-07-28 01:45:00', '2025-07-28 03:51:16'),
(6, 'Gestion de Usuarios', '👤', '2025-07-28 04:02:53', NULL),
(7, 'Gestion Cai', '🧾', '2025-07-29 18:23:02', NULL),
(8, 'Gestiones Cai', '🧾', '2025-07-29 19:36:20', '2025-08-03 03:45:05'),
(9, 'Gestion', '🧾', '2025-08-03 03:44:21', NULL),
(10, 'Prueba', '📂', '2025-08-03 03:58:40', '2025-08-05 05:02:11'),
(11, 'Caja', '🗃️', '2025-08-05 05:01:56', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2014_10_12_200000_add_two_factor_columns_to_users_table', 1),
(4, '2019_08_19_000000_create_failed_jobs_table', 1),
(5, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(6, '2020_05_21_100000_create_teams_table', 1),
(7, '2020_05_21_200000_create_team_user_table', 1),
(8, '2020_05_21_300000_create_team_invitations_table', 1),
(9, '2022_02_22_015558_create_sessions_table', 1),
(10, '0001_01_01_000001_create_cache_table', 2),
(11, '2025_08_06_094419_add_unique_constraints_to_cliente_table', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `municipio`
--

CREATE TABLE `municipio` (
  `id` int(11) NOT NULL,
  `nombre` varchar(45) DEFAULT NULL,
  `departamento_id` int(11) NOT NULL,
  `users_registro_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `municipio`
--

INSERT INTO `municipio` (`id`, `nombre`, `departamento_id`, `users_registro_id`, `created_at`, `updated_at`) VALUES
(1, 'La Ceiba', 1, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(2, 'Tela', 1, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(3, 'Jutiapa', 1, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(4, 'La Masica', 1, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(5, 'Arizona', 1, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(7, 'Tocoa', 2, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(8, 'Bonito Oriental', 2, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(9, 'Sabá', 2, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(10, 'Iriona', 2, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(11, 'Comayagua', 3, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(12, 'La Libertad', 3, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(13, 'Esquías', 3, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(14, 'Lejamaní', 3, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(15, 'San Jerónimo', 3, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(16, 'Santa Rosa de Copán', 4, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(17, 'La Entrada', 4, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(18, 'Copán Ruinas', 4, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(19, 'Florida', 4, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(20, 'Dulce Nombre', 4, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(21, 'Choluteca', 5, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(22, 'Pespire', 5, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(23, 'San Marcos de Colón', 5, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(24, 'El Triunfo', 5, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(25, 'Namasigüe', 5, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(26, 'San Pedro Sula', 6, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(27, 'Choloma', 6, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(28, 'Puerto Cortés', 6, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(29, 'Villanueva', 6, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(30, 'La Lima', 6, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(31, 'Danlí', 7, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(32, 'Yuscarán', 7, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(33, 'Teupasenti', 7, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(34, 'Trojes', 7, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(35, 'Jacaleapa', 7, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(36, 'Distrito Central', 8, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(37, 'Valle de Ángeles', 8, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(38, 'Tatumbla', 8, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(39, 'Guaimaca', 8, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(40, 'Ojojona', 8, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(41, 'Puerto Lempira', 9, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(42, 'Brus Laguna', 9, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(43, 'Ahuas', 9, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(44, 'Juan Francisco Bulnes', 9, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(45, 'Villeda Morales', 9, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(46, 'La Esperanza', 10, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(47, 'Intibucá', 10, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(48, 'Camasca', 10, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(49, 'Colomoncagua', 10, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(50, 'San Francisco de Opalaca', 10, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(51, 'Roatán', 11, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(52, 'Utila', 11, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(53, 'Guanaja', 11, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(54, 'La Paz', 12, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(55, 'Marcala', 12, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(56, 'Cabañas', 12, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(57, 'Santa Ana', 12, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(58, 'San José', 12, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(59, 'Gracias', 13, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(60, 'Erandique', 13, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(61, 'La Campa', 13, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(62, 'La Iguala', 13, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(63, 'San Manuel Colohete', 13, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(64, 'Ocotepeque', 14, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(65, 'Sensenti', 14, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(66, 'La Labor', 14, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(67, 'Lucerna', 14, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(68, 'Sinuapa', 14, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(69, 'Juticalpa', 15, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(70, 'Catacamas', 15, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(71, 'Campamento', 15, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(72, 'San Francisco de la Paz', 15, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(73, 'Gualaco', 15, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(74, 'Santa Bárbara', 16, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(75, 'Macuelizo', 16, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(76, 'San Luis', 16, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(77, 'Quimistán', 16, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(78, 'Atima', 16, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(79, 'Nacaome', 17, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(80, 'San Lorenzo', 17, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(81, 'Amapala', 17, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(82, 'Caridad', 17, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(83, 'Goascorán', 17, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(84, 'Yoro', 18, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(85, 'El Progreso', 18, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(86, 'Olanchito', 18, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(87, 'Morazán', 18, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(88, 'Victoria', 18, 14, '2025-08-03 03:30:35', '2025-08-03 03:30:35'),
(89, 'Trujillo', 2, 14, '2025-08-05 18:23:36', '2025-08-05 18:23:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`email`, `token`, `created_at`) VALUES
('johann_ruiz14@hotmail.com', '$2y$12$7kwQ.UVvn0wHHCDXYKe2ieNVHeNdtrZDrlRTZkesGXHFvU0Qzbn8S', '2025-07-16 14:36:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `id` int(11) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `isv_id` int(11) NOT NULL,
  `descripcion` varchar(45) NOT NULL,
  `descuento_unitario` decimal(16,2) NOT NULL,
  `descuento_tercera` tinyint(1) DEFAULT NULL,
  `descuento_cuarta` tinyint(1) DEFAULT NULL,
  `precio_base` double NOT NULL,
  `ultimo_costo_compra` double DEFAULT NULL,
  `costo_promedio` double DEFAULT NULL,
  `codigo_barra` varchar(100) DEFAULT NULL,
  `codigo_estatal` varchar(45) DEFAULT NULL,
  `estado_id` int(11) NOT NULL,
  `subcategoria_id` int(11) NOT NULL,
  `marca_id` int(11) NOT NULL,
  `unidad_medida_venta_id` int(11) NOT NULL,
  `precio1` decimal(16,2) DEFAULT NULL,
  `precio2` decimal(16,2) DEFAULT NULL,
  `precio3` decimal(16,2) DEFAULT NULL,
  `precio4` decimal(16,2) DEFAULT NULL,
  `users_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `recibido_bodega`
--

CREATE TABLE `recibido_bodega` (
  `id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `seccion_id` int(11) NOT NULL,
  `cantidad_compra_lote` int(11) DEFAULT NULL,
  `cantidad_inicial_seccion` int(11) DEFAULT NULL,
  `cantidad_disponible` int(11) DEFAULT NULL,
  `fecha_recibido` date DEFAULT NULL,
  `fecha_expiracion` date DEFAULT NULL,
  `comentario` varchar(150) DEFAULT NULL,
  `unidades_compra` varchar(45) DEFAULT NULL,
  `unidad_compra_id` int(11) NOT NULL,
  `users_registro_id` bigint(20) UNSIGNED NOT NULL,
  `estado_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `txt_nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` datetime DEFAULT NULL,
  `updated_user` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_user` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `txt_nombre`, `estado`, `updated_at`, `updated_user`, `created_at`, `created_user`) VALUES
(2, 'Admin', 1, '2025-07-24 21:14:09', NULL, '2025-07-24 21:14:09', 1),
(9, 'Administrador', 1, '2025-08-10 18:58:49', NULL, '2025-08-10 18:58:49', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_permiso`
--

CREATE TABLE `rol_permiso` (
  `id` int(11) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `updated_at` datetime DEFAULT NULL,
  `updated_user` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_user` int(11) DEFAULT NULL,
  `menu_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rol_permiso`
--

INSERT INTO `rol_permiso` (`id`, `rol_id`, `estado`, `updated_at`, `updated_user`, `created_at`, `created_user`, `menu_id`) VALUES
(70, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 74),
(71, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 78),
(72, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 85),
(73, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 83),
(74, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 79),
(75, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 68),
(76, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 80),
(77, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 71),
(78, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 75),
(79, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 55),
(80, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 84),
(81, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 58),
(82, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 70),
(83, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 64),
(84, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 82),
(85, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 67),
(86, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 63),
(87, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 77),
(88, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 81),
(89, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 59),
(90, 9, 1, NULL, NULL, '2025-08-10 18:58:49', 1, 73);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seccion`
--

CREATE TABLE `seccion` (
  `id` int(11) NOT NULL,
  `descripcion` varchar(45) DEFAULT NULL,
  `numeracion` varchar(45) DEFAULT NULL,
  `estado_id` int(11) NOT NULL,
  `segmento_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `segmento`
--

CREATE TABLE `segmento` (
  `id` int(11) NOT NULL,
  `descripcion` varchar(45) DEFAULT NULL,
  `bodega_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('BTO0A5cpuT3B2wJ911j0Jkl0SeIgbCDVxDaC9qCW', 15, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoickp2SUJjc3FDT2JMNGtsbHFFYWJKT012NEZ0UVlqYk80YzVxbUNldyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kYXNoYm9hcmQiO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxNTt9', 1754874077);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `subcategoria`
--

CREATE TABLE `subcategoria` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `categoria_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `teams`
--

CREATE TABLE `teams` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `personal_team` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `team_invitations`
--

CREATE TABLE `team_invitations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `team_id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `team_user`
--

CREATE TABLE `team_user` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `team_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tienda`
--

CREATE TABLE `tienda` (
  `id` int(11) NOT NULL,
  `denominacion_social` varchar(145) DEFAULT NULL,
  `descripcion` varchar(345) DEFAULT NULL,
  `telefono` varchar(45) DEFAULT NULL,
  `celular` varchar(45) DEFAULT NULL,
  `correo` varchar(45) DEFAULT NULL,
  `tipo_tienda_id` int(11) NOT NULL,
  `estado_id` int(11) NOT NULL,
  `users_creador_id` bigint(20) UNSIGNED NOT NULL,
  `numero_sucursal` varchar(45) DEFAULT NULL,
  `identificador_legal` varchar(45) DEFAULT NULL COMMENT 'La idea es implementaro algo como el ics, etc etc',
  `direccion_sucursal_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `empresa_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `tienda`
--

INSERT INTO `tienda` (`id`, `denominacion_social`, `descripcion`, `telefono`, `celular`, `correo`, `tipo_tienda_id`, `estado_id`, `users_creador_id`, `numero_sucursal`, `identificador_legal`, `direccion_sucursal_id`, `created_at`, `updated_at`, `empresa_id`) VALUES
(1, 'Paperland', 'test', '2222-2222', '9999-9999', 'test@gmail.com', 2, 1, 14, '1233', 'ICS-0001', 5, '2025-08-03 03:32:40', '2025-08-05 21:11:19', 1),
(6, 'az', 'as', '99999999', '99999999', 'Johann_ruiz@hotmail.com', 2, 1, 14, 'ics-0001', '312312312312', 10, '2025-08-11 01:02:37', NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_cliente`
--

CREATE TABLE `tipo_cliente` (
  `id` int(11) NOT NULL,
  `nombre` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `tipo_cliente`
--

INSERT INTO `tipo_cliente` (`id`, `nombre`, `created_at`, `updated_at`) VALUES
(1, 'Cliente A', '2025-08-06 02:22:48', NULL),
(2, 'Cliente B', '2025-08-06 02:22:48', NULL),
(3, 'Proveedor', '2025-08-06 21:11:53', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_direccion`
--

CREATE TABLE `tipo_direccion` (
  `id` int(11) NOT NULL,
  `nombre` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `tipo_direccion`
--

INSERT INTO `tipo_direccion` (`id`, `nombre`, `created_at`, `updated_at`) VALUES
(1, 'Cliente', '2025-08-03 03:22:27', '2025-08-03 03:22:27'),
(2, 'Proveedor', '2025-08-03 03:22:27', '2025-08-03 03:22:27'),
(3, 'Tienda', '2025-08-03 03:23:22', '2025-08-03 03:23:22'),
(4, 'Usuario', '2025-08-03 03:23:22', '2025-08-03 03:23:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_documento_fiscal`
--

CREATE TABLE `tipo_documento_fiscal` (
  `id` int(11) NOT NULL COMMENT 'El tipo de documento fical puede ser, Factura, Nota de crédito, Nota de débito, Retención, etc.',
  `nombre` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `users_registro_id` bigint(20) UNSIGNED NOT NULL,
  `estado_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `tipo_documento_fiscal`
--

INSERT INTO `tipo_documento_fiscal` (`id`, `nombre`, `created_at`, `updated_at`, `users_registro_id`, `estado_id`) VALUES
(1, '01-Factura', '2025-08-03 03:21:50', '2025-08-03 03:21:50', 14, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_facturacion`
--

CREATE TABLE `tipo_facturacion` (
  `id` int(11) NOT NULL,
  `nombre` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `tipo_facturacion`
--

INSERT INTO `tipo_facturacion` (`id`, `nombre`, `created_at`, `updated_at`) VALUES
(1, 'Venta Normal', NULL, NULL),
(2, 'CrÃ©dito', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_pago`
--

CREATE TABLE `tipo_pago` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `update_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `tipo_pago`
--

INSERT INTO `tipo_pago` (`id`, `nombre`, `created_at`, `update_at`) VALUES
(1, 'Efectivo', '2025-08-08 05:12:57', NULL),
(2, 'Tarjeta(POS)', '2025-08-08 05:12:57', NULL),
(3, 'Cheque', '2025-08-08 05:13:40', NULL),
(4, 'Transferencia', '2025-08-10 23:18:12', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_persona`
--

CREATE TABLE `tipo_persona` (
  `id` int(11) NOT NULL,
  `nombre` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `tipo_persona`
--

INSERT INTO `tipo_persona` (`id`, `nombre`, `created_at`, `updated_at`) VALUES
(1, 'Natural', '2025-08-06 02:23:26', NULL),
(2, 'Juridica', '2025-08-06 02:23:26', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_tienda`
--

CREATE TABLE `tipo_tienda` (
  `id` int(11) NOT NULL COMMENT 'La idea es que el tipo de sucursal, nos indique si es una principal o sucursal, esto para tener una centralización y distinción del mismo.',
  `nombre` varchar(45) DEFAULT NULL,
  `users_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `tipo_tienda`
--

INSERT INTO `tipo_tienda` (`id`, `nombre`, `users_id`, `created_at`, `updated_at`) VALUES
(1, 'Principal', 14, '2025-08-03 03:20:56', '2025-08-03 03:20:56'),
(2, 'Sucursal', 14, '2025-08-03 03:20:56', '2025-08-03 03:20:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transaccion`
--

CREATE TABLE `transaccion` (
  `id` int(11) NOT NULL,
  `caja_id` int(11) NOT NULL,
  `transaccion` varchar(45) DEFAULT NULL,
  `efectivo` decimal(16,2) DEFAULT NULL,
  `tarjeta` decimal(16,2) DEFAULT NULL,
  `cheque` decimal(16,2) DEFAULT NULL,
  `descripcion` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `update_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidad_compra`
--

CREATE TABLE `unidad_compra` (
  `id` int(11) NOT NULL,
  `unidad` int(11) DEFAULT NULL,
  `nombre` varchar(45) DEFAULT NULL,
  `simbolo` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidad_medida`
--

CREATE TABLE `unidad_medida` (
  `id` int(11) NOT NULL,
  `unidad` int(11) DEFAULT NULL,
  `nombre` varchar(45) DEFAULT NULL,
  `simbolo` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `two_factor_secret` text COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text COLLATE utf8mb4_unicode_ci,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_team_id` bigint(20) UNSIGNED DEFAULT NULL,
  `profile_photo_path` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_user` bigint(20) UNSIGNED DEFAULT NULL,
  `update_user` bigint(20) UNSIGNED DEFAULT NULL,
  `roles_id` int(11) NOT NULL DEFAULT '1',
  `estado_id` int(11) NOT NULL,
  `tienda_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `remember_token`, `current_team_id`, `profile_photo_path`, `created_user`, `update_user`, `roles_id`, `estado_id`, `tienda_id`, `created_at`, `updated_at`) VALUES
(14, 'Super  Usuario', 'zenvyadmin@cadss.hn', NULL, '$2y$12$O/6shw15r7sQ3EhjZzkqVuw6Cav4KbdIlmaVxMGEax4NS05ek8Rse', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, 1, '2025-08-11 00:55:53', '2025-08-11 00:55:53'),
(15, 'Administrador a', 'Administrador@Administrador.com', NULL, '$2y$12$WVl.BWM4jAODPTZZ4A3wHe6GuAVS2X4nZSWV.989xP0cA7uUhV6ee', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 9, 1, 1, '2025-08-11 01:00:50', '2025-08-11 01:00:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_detalle`
--

CREATE TABLE `user_detalle` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `primer_nombre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `segundo_nombre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `primer_apellido` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `segundo_apellido` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_user` bigint(20) UNSIGNED DEFAULT NULL,
  `update_user` bigint(20) UNSIGNED DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `genero` enum('Masculino','Femenino','Otro') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `identidad` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `users_id` bigint(20) UNSIGNED NOT NULL,
  `estado_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `user_detalle`
--

INSERT INTO `user_detalle` (`id`, `primer_nombre`, `segundo_nombre`, `primer_apellido`, `segundo_apellido`, `direccion`, `telefono`, `created_user`, `update_user`, `fecha_nacimiento`, `genero`, `identidad`, `users_id`, `estado_id`, `created_at`, `updated_at`) VALUES
(14, 'Super ', '', 'Usuario', '', NULL, NULL, NULL, NULL, NULL, NULL, 'Super Usuario', 14, 1, '2025-08-11 00:55:53', '2025-08-11 00:55:53'),
(15, 'Administrador', '', 'a', '', NULL, NULL, NULL, NULL, NULL, NULL, 'Administrador', 15, 1, '2025-08-11 01:00:50', '2025-08-11 01:00:50');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bodega`
--
ALTER TABLE `bodega`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bodega_direccion1_idx` (`direccion_id`),
  ADD KEY `fk_bodega_estado1_idx` (`estado_id`),
  ADD KEY `fk_bodega_tienda1_idx` (`tienda_id`);

--
-- Indices de la tabla `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indices de la tabla `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indices de la tabla `cai`
--
ALTER TABLE `cai`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cai_tipo_documento_fiscal1_idx` (`tipo_documento_fiscal_id`),
  ADD KEY `fk_cai_tienda1_idx` (`tienda_id`),
  ADD KEY `fk_cai_users1_idx` (`users_registro_id`),
  ADD KEY `fk_cai_estado1_idx` (`estado_id`);

--
-- Indices de la tabla `caja`
--
ALTER TABLE `caja`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_caja_users1_idx` (`users_id`),
  ADD KEY `fk_caja_tienda1_idx` (`tienda_id`);

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `cierre_de_caja`
--
ALTER TABLE `cierre_de_caja`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cierre_de_caja_caja1_idx` (`caja_id`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cliente_direccion1_idx` (`direccion_id`),
  ADD KEY `fk_cliente_estado1_idx` (`estado_id`),
  ADD KEY `fk_cliente_tipo_persona1_idx` (`tipo_persona_id`),
  ADD KEY `fk_cliente_tipo_cliente1_idx` (`tipo_cliente_id`),
  ADD KEY `fk_cliente_users1_idx` (`users_id`);

--
-- Indices de la tabla `compra`
--
ALTER TABLE `compra`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_compra_estado1_idx` (`estado_id`),
  ADD KEY `fk_compra_cliente1_idx` (`cliente_id`);

--
-- Indices de la tabla `compra_has_producto`
--
ALTER TABLE `compra_has_producto`
  ADD PRIMARY KEY (`id`,`compra_id`,`producto_id`),
  ADD KEY `fk_Compra_has_producto_compra1_idx` (`compra_id`),
  ADD KEY `fk_Compra_has_producto_producto1_idx` (`producto_id`),
  ADD KEY `fk_Compra_has_producto_unidad_compra1_idx` (`unidad_compra_id`);

--
-- Indices de la tabla `departamento`
--
ALTER TABLE `departamento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_departamento_users1_idx` (`user_registro_id`);

--
-- Indices de la tabla `descuentos`
--
ALTER TABLE `descuentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_descuentos_users1_idx` (`users_id`);

--
-- Indices de la tabla `descuento_adulto`
--
ALTER TABLE `descuento_adulto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_descuento_adulto_factura1_idx` (`factura_id`);

--
-- Indices de la tabla `detalle_factura_lote`
--
ALTER TABLE `detalle_factura_lote`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_detalle_factura_lote_factura1_idx` (`factura_id`),
  ADD KEY `fk_detalle_factura_lote_producto1_idx` (`producto_id`),
  ADD KEY `fk_detalle_factura_lote_recibido_bodega1_idx` (`recibido_bodega_id`);

--
-- Indices de la tabla `direccion`
--
ALTER TABLE `direccion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_direccion_sucursal_municipio1_idx` (`municipio_id`),
  ADD KEY `fk_direccion_sucursal_estado1_idx` (`estado_id`),
  ADD KEY `fk_direccion_tipo_direccion1_idx` (`tipo_direccion_id`);

--
-- Indices de la tabla `distribucion_stock`
--
ALTER TABLE `distribucion_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_distribucion_stock_recibido_bodega1_idx` (`recibido_bodega_id`),
  ADD KEY `fk_distribucion_stock_users1_idx` (`users_id`);

--
-- Indices de la tabla `empresa`
--
ALTER TABLE `empresa`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `estado`
--
ALTER TABLE `estado`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `estado_factura`
--
ALTER TABLE `estado_factura`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `factura`
--
ALTER TABLE `factura`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_factura_cai1_idx` (`cai_id`),
  ADD KEY `fk_factura_tipo_facturacion1_idx` (`tipo_facturacion_id`),
  ADD KEY `fk_factura_users1_idx` (`users_id`),
  ADD KEY `fk_factura_estado_factura1_idx` (`estado_factura_id`),
  ADD KEY `fk_factura_descuentos1_idx` (`descuentos_id`);

--
-- Indices de la tabla `factura_has_pago`
--
ALTER TABLE `factura_has_pago`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_factura_has_pago_tipo_pago1_idx` (`tipo_pago_id`),
  ADD KEY `fk_factura_has_pago_factura1_idx` (`factura_id`);

--
-- Indices de la tabla `factura_has_producto`
--
ALTER TABLE `factura_has_producto`
  ADD PRIMARY KEY (`factura_id`,`producto_id`,`seccion_id`),
  ADD KEY `fk_factura_has_producto_producto1_idx` (`producto_id`),
  ADD KEY `fk_factura_has_producto_factura1_idx` (`factura_id`),
  ADD KEY `fk_factura_has_producto_seccion1_idx` (`seccion_id`),
  ADD KEY `fk_factura_has_producto_unidad_medida1_idx` (`unidad_medida_id`);

--
-- Indices de la tabla `factura_has_stock`
--
ALTER TABLE `factura_has_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_factura_has_stock_distribucion_stock1_idx` (`distribucion_stock_id`),
  ADD KEY `fk_factura_has_stock_factura1_idx` (`factura_id`);

--
-- Indices de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indices de la tabla `gestion_cai`
--
ALTER TABLE `gestion_cai`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_gestion_cai_cai1_idx` (`cai_id`),
  ADD KEY `fk_gestion_cai_estado1_idx` (`estado_id`);

--
-- Indices de la tabla `gestion_diferencia`
--
ALTER TABLE `gestion_diferencia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_gestion_diferencia_cierre_de_caja1_idx` (`cierre_de_caja_id`),
  ADD KEY `fk_gestion_diferencia_users1_idx` (`users_id`);

--
-- Indices de la tabla `isv`
--
ALTER TABLE `isv`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_isv_estado1_idx` (`estado_id`);

--
-- Indices de la tabla `jornada`
--
ALTER TABLE `jornada`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_jornada_tienda1_idx` (`tienda_id`);

--
-- Indices de la tabla `marca`
--
ALTER TABLE `marca`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `fk_menu_estado1_idx` (`estado_id`);

--
-- Indices de la tabla `menu_grupo`
--
ALTER TABLE `menu_grupo`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `municipio`
--
ALTER TABLE `municipio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_municipio_departamento1_idx` (`departamento_id`),
  ADD KEY `fk_municipio_users1_idx` (`users_registro_id`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD KEY `password_reset_tokens_email_index` (`email`);

--
-- Indices de la tabla `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_productos_estado1_idx` (`estado_id`),
  ADD KEY `fk_productos_subcategoria1_idx` (`subcategoria_id`),
  ADD KEY `fk_productos_marca1_idx` (`marca_id`),
  ADD KEY `fk_productos_unidad_medida1_idx` (`unidad_medida_venta_id`),
  ADD KEY `fk_producto_users1_idx` (`users_id`),
  ADD KEY `fk_producto_isv1_idx` (`isv_id`);

--
-- Indices de la tabla `recibido_bodega`
--
ALTER TABLE `recibido_bodega`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_recibido_bodega_producto1_idx` (`producto_id`),
  ADD KEY `fk_recibido_bodega_seccion1_idx` (`seccion_id`),
  ADD KEY `fk_recibido_bodega_unidad_medida1_idx` (`unidad_compra_id`),
  ADD KEY `fk_recibido_bodega_users1_idx` (`users_registro_id`),
  ADD KEY `fk_recibido_bodega_estado1_idx` (`estado_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  ADD PRIMARY KEY (`id`),
  ADD KEY `rol_id` (`rol_id`),
  ADD KEY `fk_rol_permiso_menu1_idx` (`menu_id`);

--
-- Indices de la tabla `seccion`
--
ALTER TABLE `seccion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_seccion_estado1_idx` (`estado_id`),
  ADD KEY `fk_seccion_segmento1_idx` (`segmento_id`);

--
-- Indices de la tabla `segmento`
--
ALTER TABLE `segmento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_segmento_bodega1_idx` (`bodega_id`);

--
-- Indices de la tabla `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indices de la tabla `subcategoria`
--
ALTER TABLE `subcategoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_subcategoria_categoría1_idx` (`categoria_id`);

--
-- Indices de la tabla `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teams_user_id_index` (`user_id`);

--
-- Indices de la tabla `team_invitations`
--
ALTER TABLE `team_invitations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `team_invitations_team_id_email_unique` (`team_id`,`email`);

--
-- Indices de la tabla `team_user`
--
ALTER TABLE `team_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `team_user_team_id_user_id_unique` (`team_id`,`user_id`);

--
-- Indices de la tabla `tienda`
--
ALTER TABLE `tienda`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tienda_tipo_tienda1_idx` (`tipo_tienda_id`),
  ADD KEY `fk_tienda_estado1_idx` (`estado_id`),
  ADD KEY `fk_tienda_users1_idx` (`users_creador_id`),
  ADD KEY `fk_tienda_direccion_sucursal1_idx` (`direccion_sucursal_id`),
  ADD KEY `fk_tienda_empresa1_idx` (`empresa_id`);

--
-- Indices de la tabla `tipo_cliente`
--
ALTER TABLE `tipo_cliente`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tipo_direccion`
--
ALTER TABLE `tipo_direccion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tipo_documento_fiscal`
--
ALTER TABLE `tipo_documento_fiscal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tipo_documento_fiscal_users1_idx` (`users_registro_id`),
  ADD KEY `fk_tipo_documento_fiscal_estado1_idx` (`estado_id`);

--
-- Indices de la tabla `tipo_facturacion`
--
ALTER TABLE `tipo_facturacion`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tipo_pago`
--
ALTER TABLE `tipo_pago`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tipo_persona`
--
ALTER TABLE `tipo_persona`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tipo_tienda`
--
ALTER TABLE `tipo_tienda`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tipo_sucursal_users1_idx` (`users_id`);

--
-- Indices de la tabla `transaccion`
--
ALTER TABLE `transaccion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_transaccion_caja1_idx` (`caja_id`);

--
-- Indices de la tabla `unidad_compra`
--
ALTER TABLE `unidad_compra`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `unidad_medida`
--
ALTER TABLE `unidad_medida`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `fk_users_roles1_idx` (`roles_id`),
  ADD KEY `fk_users_estado1_idx` (`estado_id`),
  ADD KEY `fk_users_tienda1_idx` (`tienda_id`);

--
-- Indices de la tabla `user_detalle`
--
ALTER TABLE `user_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mi_actor_users1_idx` (`users_id`),
  ADD KEY `fk_user_detalle_estado1_idx` (`estado_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bodega`
--
ALTER TABLE `bodega`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `cai`
--
ALTER TABLE `cai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `caja`
--
ALTER TABLE `caja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `cierre_de_caja`
--
ALTER TABLE `cierre_de_caja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `compra`
--
ALTER TABLE `compra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `compra_has_producto`
--
ALTER TABLE `compra_has_producto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `departamento`
--
ALTER TABLE `departamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `descuentos`
--
ALTER TABLE `descuentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `descuento_adulto`
--
ALTER TABLE `descuento_adulto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `detalle_factura_lote`
--
ALTER TABLE `detalle_factura_lote`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `direccion`
--
ALTER TABLE `direccion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `distribucion_stock`
--
ALTER TABLE `distribucion_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `empresa`
--
ALTER TABLE `empresa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `estado`
--
ALTER TABLE `estado`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `estado_factura`
--
ALTER TABLE `estado_factura`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `factura`
--
ALTER TABLE `factura`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT de la tabla `factura_has_pago`
--
ALTER TABLE `factura_has_pago`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT de la tabla `factura_has_stock`
--
ALTER TABLE `factura_has_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `gestion_cai`
--
ALTER TABLE `gestion_cai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `gestion_diferencia`
--
ALTER TABLE `gestion_diferencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `isv`
--
ALTER TABLE `isv`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `jornada`
--
ALTER TABLE `jornada`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `marca`
--
ALTER TABLE `marca`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT de la tabla `menu_grupo`
--
ALTER TABLE `menu_grupo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `municipio`
--
ALTER TABLE `municipio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT de la tabla `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `recibido_bodega`
--
ALTER TABLE `recibido_bodega`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT de la tabla `seccion`
--
ALTER TABLE `seccion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `segmento`
--
ALTER TABLE `segmento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `subcategoria`
--
ALTER TABLE `subcategoria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de la tabla `teams`
--
ALTER TABLE `teams`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `team_invitations`
--
ALTER TABLE `team_invitations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `team_user`
--
ALTER TABLE `team_user`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tienda`
--
ALTER TABLE `tienda`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `tipo_cliente`
--
ALTER TABLE `tipo_cliente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tipo_direccion`
--
ALTER TABLE `tipo_direccion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `tipo_documento_fiscal`
--
ALTER TABLE `tipo_documento_fiscal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'El tipo de documento fical puede ser, Factura, Nota de crédito, Nota de débito, Retención, etc.', AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `tipo_facturacion`
--
ALTER TABLE `tipo_facturacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `tipo_pago`
--
ALTER TABLE `tipo_pago`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `tipo_persona`
--
ALTER TABLE `tipo_persona`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `tipo_tienda`
--
ALTER TABLE `tipo_tienda`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'La idea es que el tipo de sucursal, nos indique si es una principal o sucursal, esto para tener una centralización y distinción del mismo.', AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `transaccion`
--
ALTER TABLE `transaccion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `unidad_compra`
--
ALTER TABLE `unidad_compra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `unidad_medida`
--
ALTER TABLE `unidad_medida`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `user_detalle`
--
ALTER TABLE `user_detalle`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `bodega`
--
ALTER TABLE `bodega`
  ADD CONSTRAINT `fk_bodega_direccion1` FOREIGN KEY (`direccion_id`) REFERENCES `direccion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_bodega_estado1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_bodega_tienda1` FOREIGN KEY (`tienda_id`) REFERENCES `tienda` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `cai`
--
ALTER TABLE `cai`
  ADD CONSTRAINT `fk_cai_estado1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_cai_tienda1` FOREIGN KEY (`tienda_id`) REFERENCES `tienda` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_cai_tipo_documento_fiscal1` FOREIGN KEY (`tipo_documento_fiscal_id`) REFERENCES `tipo_documento_fiscal` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_cai_users1` FOREIGN KEY (`users_registro_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `caja`
--
ALTER TABLE `caja`
  ADD CONSTRAINT `fk_caja_tienda1` FOREIGN KEY (`tienda_id`) REFERENCES `tienda` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_caja_users1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `cierre_de_caja`
--
ALTER TABLE `cierre_de_caja`
  ADD CONSTRAINT `fk_cierre_de_caja_caja1` FOREIGN KEY (`caja_id`) REFERENCES `caja` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD CONSTRAINT `fk_cliente_direccion1` FOREIGN KEY (`direccion_id`) REFERENCES `direccion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_cliente_estado1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_cliente_tipo_cliente1` FOREIGN KEY (`tipo_cliente_id`) REFERENCES `tipo_cliente` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_cliente_tipo_persona1` FOREIGN KEY (`tipo_persona_id`) REFERENCES `tipo_persona` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_cliente_users1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `compra`
--
ALTER TABLE `compra`
  ADD CONSTRAINT `fk_compra_cliente1` FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_compra_estado1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `compra_has_producto`
--
ALTER TABLE `compra_has_producto`
  ADD CONSTRAINT `fk_Compra_has_producto_compra1` FOREIGN KEY (`compra_id`) REFERENCES `compra` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Compra_has_producto_producto1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_Compra_has_producto_unidad_compra1` FOREIGN KEY (`unidad_compra_id`) REFERENCES `unidad_compra` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `departamento`
--
ALTER TABLE `departamento`
  ADD CONSTRAINT `fk_departamento_users1` FOREIGN KEY (`user_registro_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `descuentos`
--
ALTER TABLE `descuentos`
  ADD CONSTRAINT `fk_descuentos_users1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `descuento_adulto`
--
ALTER TABLE `descuento_adulto`
  ADD CONSTRAINT `fk_descuento_adulto_factura1` FOREIGN KEY (`factura_id`) REFERENCES `factura` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `detalle_factura_lote`
--
ALTER TABLE `detalle_factura_lote`
  ADD CONSTRAINT `fk_detalle_factura_lote_factura1` FOREIGN KEY (`factura_id`) REFERENCES `factura` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_detalle_factura_lote_producto1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_detalle_factura_lote_recibido_bodega1` FOREIGN KEY (`recibido_bodega_id`) REFERENCES `recibido_bodega` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `direccion`
--
ALTER TABLE `direccion`
  ADD CONSTRAINT `fk_direccion_sucursal_estado1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_direccion_sucursal_municipio1` FOREIGN KEY (`municipio_id`) REFERENCES `municipio` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_direccion_tipo_direccion1` FOREIGN KEY (`tipo_direccion_id`) REFERENCES `tipo_direccion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `distribucion_stock`
--
ALTER TABLE `distribucion_stock`
  ADD CONSTRAINT `fk_distribucion_stock_recibido_bodega1` FOREIGN KEY (`recibido_bodega_id`) REFERENCES `recibido_bodega` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_distribucion_stock_users1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `factura`
--
ALTER TABLE `factura`
  ADD CONSTRAINT `fk_factura_cai1` FOREIGN KEY (`cai_id`) REFERENCES `cai` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_factura_descuentos1` FOREIGN KEY (`descuentos_id`) REFERENCES `descuentos` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_factura_estado_factura1` FOREIGN KEY (`estado_factura_id`) REFERENCES `estado_factura` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_factura_tipo_facturacion1` FOREIGN KEY (`tipo_facturacion_id`) REFERENCES `tipo_facturacion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_factura_users1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `factura_has_pago`
--
ALTER TABLE `factura_has_pago`
  ADD CONSTRAINT `fk_factura_has_pago_factura1` FOREIGN KEY (`factura_id`) REFERENCES `factura` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_factura_has_pago_tipo_pago1` FOREIGN KEY (`tipo_pago_id`) REFERENCES `tipo_pago` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `factura_has_producto`
--
ALTER TABLE `factura_has_producto`
  ADD CONSTRAINT `fk_factura_has_producto_factura1` FOREIGN KEY (`factura_id`) REFERENCES `factura` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_factura_has_producto_producto1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_factura_has_producto_seccion1` FOREIGN KEY (`seccion_id`) REFERENCES `seccion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_factura_has_producto_unidad_medida1` FOREIGN KEY (`unidad_medida_id`) REFERENCES `unidad_medida` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `factura_has_stock`
--
ALTER TABLE `factura_has_stock`
  ADD CONSTRAINT `fk_factura_has_stock_distribucion_stock1` FOREIGN KEY (`distribucion_stock_id`) REFERENCES `distribucion_stock` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_factura_has_stock_factura1` FOREIGN KEY (`factura_id`) REFERENCES `factura` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `gestion_cai`
--
ALTER TABLE `gestion_cai`
  ADD CONSTRAINT `fk_gestion_cai_cai1` FOREIGN KEY (`cai_id`) REFERENCES `cai` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_gestion_cai_estado1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `gestion_diferencia`
--
ALTER TABLE `gestion_diferencia`
  ADD CONSTRAINT `fk_gestion_diferencia_cierre_de_caja1` FOREIGN KEY (`cierre_de_caja_id`) REFERENCES `cierre_de_caja` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_gestion_diferencia_users1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `isv`
--
ALTER TABLE `isv`
  ADD CONSTRAINT `fk_isv_estado1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `jornada`
--
ALTER TABLE `jornada`
  ADD CONSTRAINT `fk_jornada_tienda1` FOREIGN KEY (`tienda_id`) REFERENCES `tienda` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `menu`
--
ALTER TABLE `menu`
  ADD CONSTRAINT `fk_menu_estado1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `menu_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `menu_grupo` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `municipio`
--
ALTER TABLE `municipio`
  ADD CONSTRAINT `fk_municipio_departamento1` FOREIGN KEY (`departamento_id`) REFERENCES `departamento` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_municipio_users1` FOREIGN KEY (`users_registro_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `producto`
--
ALTER TABLE `producto`
  ADD CONSTRAINT `fk_producto_isv1` FOREIGN KEY (`isv_id`) REFERENCES `isv` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_producto_users1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_productos_estado1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_productos_marca1` FOREIGN KEY (`marca_id`) REFERENCES `marca` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_productos_subcategoria1` FOREIGN KEY (`subcategoria_id`) REFERENCES `subcategoria` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_productos_unidad_medida1` FOREIGN KEY (`unidad_medida_venta_id`) REFERENCES `unidad_medida` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `recibido_bodega`
--
ALTER TABLE `recibido_bodega`
  ADD CONSTRAINT `fk_recibido_bodega_estado1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_recibido_bodega_producto1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_recibido_bodega_seccion1` FOREIGN KEY (`seccion_id`) REFERENCES `seccion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_recibido_bodega_unidad_medida1` FOREIGN KEY (`unidad_compra_id`) REFERENCES `unidad_medida` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_recibido_bodega_users1` FOREIGN KEY (`users_registro_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  ADD CONSTRAINT `fk_rol_permiso_menu1` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `rol_permiso_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `seccion`
--
ALTER TABLE `seccion`
  ADD CONSTRAINT `fk_seccion_estado1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_seccion_segmento1` FOREIGN KEY (`segmento_id`) REFERENCES `segmento` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `segmento`
--
ALTER TABLE `segmento`
  ADD CONSTRAINT `fk_segmento_bodega1` FOREIGN KEY (`bodega_id`) REFERENCES `bodega` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `subcategoria`
--
ALTER TABLE `subcategoria`
  ADD CONSTRAINT `fk_subcategoria_categoría1` FOREIGN KEY (`categoria_id`) REFERENCES `categoria` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `team_invitations`
--
ALTER TABLE `team_invitations`
  ADD CONSTRAINT `team_invitations_team_id_foreign` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tienda`
--
ALTER TABLE `tienda`
  ADD CONSTRAINT `fk_tienda_direccion_sucursal1` FOREIGN KEY (`direccion_sucursal_id`) REFERENCES `direccion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_tienda_empresa1` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_tienda_estado1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_tienda_tipo_tienda1` FOREIGN KEY (`tipo_tienda_id`) REFERENCES `tipo_tienda` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_tienda_users1` FOREIGN KEY (`users_creador_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `tipo_documento_fiscal`
--
ALTER TABLE `tipo_documento_fiscal`
  ADD CONSTRAINT `fk_tipo_documento_fiscal_estado1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_tipo_documento_fiscal_users1` FOREIGN KEY (`users_registro_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `tipo_tienda`
--
ALTER TABLE `tipo_tienda`
  ADD CONSTRAINT `fk_tipo_sucursal_users1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `transaccion`
--
ALTER TABLE `transaccion`
  ADD CONSTRAINT `fk_transaccion_caja1` FOREIGN KEY (`caja_id`) REFERENCES `caja` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_estado1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_users_roles1` FOREIGN KEY (`roles_id`) REFERENCES `roles` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_users_tienda1` FOREIGN KEY (`tienda_id`) REFERENCES `tienda` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `user_detalle`
--
ALTER TABLE `user_detalle`
  ADD CONSTRAINT `fk_mi_actor_users1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_user_detalle_estado1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
