-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 29-07-2025 a las 19:45:55
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
-- Estructura de tabla para la tabla `categoría`
--

CREATE TABLE `categoría` (
  `id` int(11) NOT NULL,
  `nombre` varchar(45) DEFAULT NULL,
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
  `tipo_persona_id` int(11) NOT NULL,
  `tipo_cliente_id` int(11) NOT NULL,
  `users_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
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
(2, 'Inactivo', NULL, NULL);

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura`
--

CREATE TABLE `factura` (
  `id` int(11) NOT NULL,
  `numero_factura` varchar(45) DEFAULT NULL,
  `cai` varchar(45) DEFAULT NULL,
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
  `cai_id` int(11) NOT NULL,
  `tipo_facturacion_id` int(11) NOT NULL,
  `comentario` varchar(250) DEFAULT NULL,
  `porc_descuento` int(11) DEFAULT NULL,
  `monto_descuento` decimal(60,2) DEFAULT NULL,
  `precio_dolar` decimal(60,2) DEFAULT NULL,
  `users_id` bigint(20) UNSIGNED NOT NULL,
  `estado_factura_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
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
  `factura_has_productocol` varchar(45) DEFAULT NULL,
  `indice` int(11) DEFAULT NULL,
  `numero_unidades_resta_inventario` int(11) DEFAULT NULL,
  `unidades_nota_credito_resta_inventario` int(11) DEFAULT NULL,
  `resta_inventario_total` int(11) DEFAULT NULL,
  `precio_unidad` decimal(60,2) DEFAULT NULL,
  `tipo_precio` varchar(45) DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `subtotal` decimal(60,2) DEFAULT NULL,
  `isv` decimal(60,2) DEFAULT NULL,
  `total` decimal(60,2) DEFAULT NULL,
  `idPrecioSeleccionado` varchar(45) DEFAULT NULL,
  `precio_seleccionado` decimal(60,2) DEFAULT NULL
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
(56, 'Cierre de Jornada', 3, 'GestionDeSucursales.CierreDeJornada', 1, NULL, 1, NULL, '2025-07-29 19:00:47'),
(57, 'CAI', 8, 'GestionesCai.Cai', 1, NULL, 1, NULL, '2025-07-29 19:36:20'),
(58, 'Producto', 2, 'Inventario.Producto', 1, NULL, 1, NULL, '2025-07-29 19:41:41');

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
(2, 'Inventario', '📦', '2025-07-28 01:44:47', NULL),
(3, 'Gestion de Sucursales', '🏢', '2025-07-28 01:44:53', NULL),
(5, 'Configuracion', '⚙️', '2025-07-28 01:45:00', '2025-07-28 03:51:16'),
(6, 'Gestion de Usuarios', '👤', '2025-07-28 04:02:53', NULL),
(7, 'Gestion Cai', '🧾', '2025-07-29 18:23:02', NULL),
(8, 'Gestiones Cai', '🧾', '2025-07-29 19:36:20', NULL);

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
(10, '0001_01_01_000001_create_cache_table', 2);

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
  `descripcion` varchar(45) NOT NULL,
  `isv` decimal(16,2) NOT NULL,
  `precio_base` double NOT NULL,
  `ultimo_costo_compra` double DEFAULT NULL,
  `costo_promedio` double DEFAULT NULL,
  `codigo_barra` varchar(100) DEFAULT NULL,
  `codigo_estatal` varchar(45) DEFAULT NULL,
  `estado_id` int(11) NOT NULL,
  `subcategoria_id` int(11) NOT NULL,
  `marca_id` int(11) NOT NULL,
  `unidad_compra` double DEFAULT NULL,
  `unidad_medida_compra_id` int(11) NOT NULL,
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
(1, 'Administrador', 1, '2025-07-26 20:42:29', NULL, '2025-07-24 16:09:00', 1),
(2, 'Admin', 1, '2025-07-24 21:14:09', NULL, '2025-07-24 21:14:09', 1),
(3, 'Roles', 1, '2025-07-25 11:23:08', NULL, '2025-07-25 10:46:05', 1),
(4, 'Menu', 0, '2025-07-26 20:44:59', NULL, '2025-07-26 20:44:51', 1);

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
(7, 3, 1, '2025-07-25 10:46:05', NULL, '2025-07-25 10:46:05', NULL, 10),
(9, 3, 1, '2025-07-25 11:23:08', NULL, '2025-07-25 11:23:08', NULL, 26),
(10, 3, 1, '2025-07-25 11:23:08', NULL, '2025-07-25 11:23:08', NULL, 27),
(11, 1, 1, NULL, NULL, '2025-07-26 20:42:29', 1, 10),
(12, 1, 1, NULL, NULL, '2025-07-26 20:42:29', 1, 26),
(13, 1, 1, NULL, NULL, '2025-07-26 20:42:29', 1, 27),
(17, 4, 1, NULL, NULL, '2025-07-26 20:44:59', 1, 10);

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
('BxRMGvW2OM6MmUnOYnZvgDzbu94AD6OHsDBbFAYb', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZWlNek4xR255Z243MTdabGQ3NmRTbVBXU3RmaGxnNmYzYUNkTERRWSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9kYXNoYm9hcmQiO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1753818117);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `subcategoria`
--

CREATE TABLE `subcategoria` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `categoría_id` int(11) NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
(1, 'Johann Ruiz', 'johann_ruiz14@hotmail.com', NULL, '$2y$12$u9JTaSsTPhURqlnk06MKheQ60NXbYqxTdtb5yq7/xM0nwE1/6JBYq', NULL, NULL, 'HuSiNz0FjYsypwcSZBCP1Nw8tFzGRSQwGJtf6Hzzv3CdWm5uhwYS5tvlZFyE', NULL, NULL, NULL, NULL, 2, 1, 0, '2025-07-16 14:04:56', '2025-07-28 03:50:50'),
(2, 'Yefry', 'yefryyo@gmail.com', NULL, '$2y$12$9.TI38cFQd69TYR5m2d9pOcE3ETorgayFFhOT4ZzKnuD0m3.Ei45i', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, '2025-07-20 17:32:50', '2025-07-20 17:32:50'),
(9, 'Pedro Ruiz', 'joma10000@gmail.com', NULL, '$2y$12$ytwvQitv/HjUq75thE5wy.KfkpEquoBpQ7cYBX4HP0e9eNus2k6v2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 4, 1, 0, '2025-07-27 04:05:39', '2025-07-27 04:06:45');

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
(7, 'Johann', 'Sebastian', 'Ruiz', NULL, NULL, '9999999', 1, NULL, NULL, NULL, '0801199717062', 1, 1, '2025-07-27 03:05:47', '2025-07-27 03:48:08'),
(8, 'Yefry', NULL, 'Ortiz', NULL, NULL, NULL, 1, NULL, NULL, NULL, '0801', 2, 1, '2025-07-27 03:48:53', NULL),
(9, 'Pedro', 'Johann S.', 'Ruiz', 'Quiroz', 'Kennedy', NULL, NULL, NULL, NULL, NULL, '08011997170626', 9, 1, '2025-07-27 04:05:39', '2025-07-27 04:05:39');

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
-- Indices de la tabla `categoría`
--
ALTER TABLE `categoría`
  ADD PRIMARY KEY (`id`);

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
-- Indices de la tabla `departamento`
--
ALTER TABLE `departamento`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_departamento_users1_idx` (`user_registro_id`);

--
-- Indices de la tabla `direccion`
--
ALTER TABLE `direccion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_direccion_sucursal_municipio1_idx` (`municipio_id`),
  ADD KEY `fk_direccion_sucursal_estado1_idx` (`estado_id`),
  ADD KEY `fk_direccion_tipo_direccion1_idx` (`tipo_direccion_id`);

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
  ADD KEY `fk_factura_estado_factura1_idx` (`estado_factura_id`);

--
-- Indices de la tabla `factura_has_producto`
--
ALTER TABLE `factura_has_producto`
  ADD PRIMARY KEY (`factura_id`,`producto_id`),
  ADD KEY `fk_factura_has_producto_producto1_idx` (`producto_id`),
  ADD KEY `fk_factura_has_producto_factura1_idx` (`factura_id`),
  ADD KEY `fk_factura_has_producto_seccion1_idx` (`seccion_id`),
  ADD KEY `fk_factura_has_producto_unidad_medida1_idx` (`unidad_medida_id`);

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
  ADD KEY `fk_productos_unidad_medida1_idx` (`unidad_medida_compra_id`),
  ADD KEY `fk_producto_users1_idx` (`users_id`);

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
  ADD KEY `fk_subcategoria_categoría1_idx` (`categoría_id`);

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
  ADD KEY `fk_tienda_direccion_sucursal1_idx` (`direccion_sucursal_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cai`
--
ALTER TABLE `cai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categoría`
--
ALTER TABLE `categoría`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `departamento`
--
ALTER TABLE `departamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `direccion`
--
ALTER TABLE `direccion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `estado`
--
ALTER TABLE `estado`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `estado_factura`
--
ALTER TABLE `estado_factura`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `factura`
--
ALTER TABLE `factura`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `marca`
--
ALTER TABLE `marca`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT de la tabla `menu_grupo`
--
ALTER TABLE `menu_grupo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `municipio`
--
ALTER TABLE `municipio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `recibido_bodega`
--
ALTER TABLE `recibido_bodega`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `seccion`
--
ALTER TABLE `seccion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `segmento`
--
ALTER TABLE `segmento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `subcategoria`
--
ALTER TABLE `subcategoria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipo_cliente`
--
ALTER TABLE `tipo_cliente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipo_direccion`
--
ALTER TABLE `tipo_direccion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipo_documento_fiscal`
--
ALTER TABLE `tipo_documento_fiscal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'El tipo de documento fical puede ser, Factura, Nota de crédito, Nota de débito, Retención, etc.';

--
-- AUTO_INCREMENT de la tabla `tipo_facturacion`
--
ALTER TABLE `tipo_facturacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipo_persona`
--
ALTER TABLE `tipo_persona`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipo_tienda`
--
ALTER TABLE `tipo_tienda`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'La idea es que el tipo de sucursal, nos indique si es una principal o sucursal, esto para tener una centralización y distinción del mismo.';

--
-- AUTO_INCREMENT de la tabla `unidad_medida`
--
ALTER TABLE `unidad_medida`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `user_detalle`
--
ALTER TABLE `user_detalle`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
-- Filtros para la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD CONSTRAINT `fk_cliente_direccion1` FOREIGN KEY (`direccion_id`) REFERENCES `direccion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_cliente_estado1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_cliente_tipo_cliente1` FOREIGN KEY (`tipo_cliente_id`) REFERENCES `tipo_cliente` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_cliente_tipo_persona1` FOREIGN KEY (`tipo_persona_id`) REFERENCES `tipo_persona` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_cliente_users1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `departamento`
--
ALTER TABLE `departamento`
  ADD CONSTRAINT `fk_departamento_users1` FOREIGN KEY (`user_registro_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `direccion`
--
ALTER TABLE `direccion`
  ADD CONSTRAINT `fk_direccion_sucursal_estado1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_direccion_sucursal_municipio1` FOREIGN KEY (`municipio_id`) REFERENCES `municipio` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_direccion_tipo_direccion1` FOREIGN KEY (`tipo_direccion_id`) REFERENCES `tipo_direccion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `factura`
--
ALTER TABLE `factura`
  ADD CONSTRAINT `fk_factura_cai1` FOREIGN KEY (`cai_id`) REFERENCES `cai` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_factura_estado_factura1` FOREIGN KEY (`estado_factura_id`) REFERENCES `estado_factura` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_factura_tipo_facturacion1` FOREIGN KEY (`tipo_facturacion_id`) REFERENCES `tipo_facturacion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_factura_users1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `factura_has_producto`
--
ALTER TABLE `factura_has_producto`
  ADD CONSTRAINT `fk_factura_has_producto_factura1` FOREIGN KEY (`factura_id`) REFERENCES `factura` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_factura_has_producto_producto1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_factura_has_producto_seccion1` FOREIGN KEY (`seccion_id`) REFERENCES `seccion` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_factura_has_producto_unidad_medida1` FOREIGN KEY (`unidad_medida_id`) REFERENCES `unidad_medida` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `gestion_cai`
--
ALTER TABLE `gestion_cai`
  ADD CONSTRAINT `fk_gestion_cai_cai1` FOREIGN KEY (`cai_id`) REFERENCES `cai` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_gestion_cai_estado1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

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
  ADD CONSTRAINT `fk_producto_users1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_productos_estado1` FOREIGN KEY (`estado_id`) REFERENCES `estado` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_productos_marca1` FOREIGN KEY (`marca_id`) REFERENCES `marca` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_productos_subcategoria1` FOREIGN KEY (`subcategoria_id`) REFERENCES `subcategoria` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_productos_unidad_medida1` FOREIGN KEY (`unidad_medida_compra_id`) REFERENCES `unidad_medida` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

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
  ADD CONSTRAINT `fk_subcategoria_categoría1` FOREIGN KEY (`categoría_id`) REFERENCES `categoría` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

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
