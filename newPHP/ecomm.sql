-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 07, 2025 at 05:01 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecomm`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `CartID` int(11) NOT NULL,
  `MemberID` int(11) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `CartStatus` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`CartID`, `MemberID`, `CreatedAt`, `CartStatus`) VALUES
(1, 5, '2025-05-07 13:18:15', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `cartitem`
--

CREATE TABLE `cartitem` (
  `CartItemID` int(11) NOT NULL,
  `CartID` int(11) DEFAULT NULL,
  `ProductID` int(11) DEFAULT NULL,
  `Quantity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cartitem`
--

INSERT INTO `cartitem` (`CartItemID`, `CartID`, `ProductID`, `Quantity`) VALUES
(1, 1, 38, 1);

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `CategoryID` int(11) NOT NULL,
  `CategoryName` varchar(255) NOT NULL,
  `CategoryDescription` text DEFAULT NULL,
  `catIMG` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`CategoryID`, `CategoryName`, `CategoryDescription`, `catIMG`) VALUES
(1, 'Skincare', 'address various skin concerns, including hydration, brightening, anti-aging, and acne treatment. ', 'skincare.jpg'),
(2, 'Makeup', 'makeup', 'makeup.jpeg'),
(3, 'Body Care', 'Bath & Shower, Body Moisturizers', 'bodycare.jpg'),
(4, 'Hair Care', 'Shampoo & Conditioners, Hair Treatment', 'haircare.webp'),
(5, 'Health & Wellness', 'Vitamin & Supplements', 'healthandwellness.avif'),
(6, 'Baby & Kids', 'Baby Skincare', 'babyandkids.jpeg'),
(7, 'Oral Care', 'ToothBrush, Toothpastes, Mouthwashes', 'oralcare.jpeg'),
(8, 'Personal Care & Hygiene', 'Feminine Hygiene Products, Sanitary Paper Products, Incontinence Care, Personal Wipes & Tissues', 'personalcareandhygiene.jpg'),
(9, 'Sexual Health', '\"Stay confident and protected — explore our range of trusted sexual wellness products for safe, comfortable, and worry-free intimacy.\"', 'sexualhealth.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `invoice`
--

CREATE TABLE `invoice` (
  `InvoiceID` int(11) NOT NULL,
  `OrderID` int(11) DEFAULT NULL,
  `InvoiceDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `InvoiceTotal` decimal(10,2) DEFAULT NULL,
  `Notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `manager`
--

CREATE TABLE `manager` (
  `ManagerID` int(11) NOT NULL,
  `ManagerUsername` varchar(100) DEFAULT NULL,
  `ManagerName` varchar(255) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `ManagerProfilePhoto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `manager`
--

INSERT INTO `manager` (`ManagerID`, `ManagerUsername`, `ManagerName`, `Password`, `ManagerProfilePhoto`) VALUES
(1, 'heng', 'ngyikheng', '$2y$10$0hxXzg/vNQu6jyXSq7HsDOk.tJtDIfeISImuymT5/28pv/mYIWEzC', '681715399a0bc_914629.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `member`
--

CREATE TABLE `member` (
  `MemberID` int(11) NOT NULL,
  `Name` varchar(100) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `PhoneNumber` varchar(20) DEFAULT NULL,
  `ProfilePhoto` varchar(255) DEFAULT NULL,
  `Gender` varchar(10) DEFAULT NULL,
  `DateOfBirth` date DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `MembershipStatus` varchar(20) DEFAULT 'Active',
  `Last_login` datetime DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member`
--

INSERT INTO `member` (`MemberID`, `Name`, `Password`, `Email`, `PhoneNumber`, `ProfilePhoto`, `Gender`, `DateOfBirth`, `Address`, `MembershipStatus`, `Last_login`, `CreatedAt`) VALUES
(1, 'heng', '$2y$10$OjkdrrPhSmHPQJUDFj8/HOJVZ6GYqWWtZ4f3eBWIgpheeQi3eyRLu', 'yikheng0613@gmail.com', '72983691', '68136c269b4e6_IMG20250428131320.jpg', 'Male', '2004-06-13', 'fdbsahfhioashdiohasiodhioashdioahsiodhioashidohsihd', 'Active', NULL, '2025-04-29 07:08:11'),
(5, 'abc', '$2y$10$ss30awLTcUVnFKESHk5VJOZA87U/r5WchQY9iSOt2Id4p.1Havcju', 'abc@gmail.com', '123456789', NULL, NULL, NULL, NULL, 'Active', NULL, '2025-05-01 08:35:34'),
(6, 'gg', '$2y$10$4zJmQQiGEJ3YNTCzzbJOOOffhrYq/777bLt.Vc2XLZ3/zjxVcbHI.', 'gg123@gmail.com', '123456789', NULL, 'Female', '2025-05-10', NULL, 'Blocked', NULL, '2025-05-01 09:03:29');

-- --------------------------------------------------------

--
-- Table structure for table `orderitem`
--

CREATE TABLE `orderitem` (
  `OrderItemID` int(11) NOT NULL,
  `OrderID` int(11) DEFAULT NULL,
  `ProductID` int(11) DEFAULT NULL,
  `Quantity` int(11) DEFAULT NULL,
  `OrderItemPrice` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orderitem`
--

INSERT INTO `orderitem` (`OrderItemID`, `OrderID`, `ProductID`, `Quantity`, `OrderItemPrice`) VALUES
(1, 1, 37, 1, 18.90),
(2, 2, 34, 1, 18.90),
(3, 3, 33, 1, 6.90),
(4, 4, 34, 1, 18.90),
(5, 5, 37, 2, 18.90),
(6, 6, 37, 1, 18.90),
(7, 7, 36, 1, 8.90),
(8, 8, 35, 1, 39.90),
(9, 9, 37, 1, 18.90),
(10, 10, 37, 1, 18.90),
(11, 11, 36, 1, 8.90),
(12, 12, 37, 1, 18.90);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `OrderID` int(11) NOT NULL,
  `MemberID` int(11) DEFAULT NULL,
  `OrderDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `OrderStatus` enum('Pending','Completed','Cancelled') DEFAULT 'Pending',
  `OrderTotalAmount` decimal(10,2) DEFAULT NULL,
  `VoucherID` int(11) DEFAULT NULL,
  `ShippingFee` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`OrderID`, `MemberID`, `OrderDate`, `OrderStatus`, `OrderTotalAmount`, `VoucherID`, `ShippingFee`) VALUES
(1, 5, '2025-05-07 13:18:34', 'Pending', 18.90, NULL, 0.00),
(2, 5, '2025-05-07 13:29:43', 'Pending', 18.90, NULL, 0.00),
(3, 5, '2025-05-07 13:43:00', 'Pending', 6.90, NULL, 0.00),
(4, 5, '2025-05-07 13:44:16', 'Pending', 18.90, NULL, 0.00),
(5, 5, '2025-05-07 13:49:11', 'Pending', 37.80, NULL, 0.00),
(6, 5, '2025-05-07 13:52:23', 'Pending', 18.90, NULL, 0.00),
(7, 5, '2025-05-07 13:54:23', 'Pending', 8.90, NULL, 0.00),
(8, 5, '2025-05-07 13:56:22', 'Pending', 39.90, NULL, 0.00),
(9, 5, '2025-05-07 14:01:08', 'Pending', 18.90, NULL, 0.00),
(10, 5, '2025-05-07 14:40:37', 'Pending', 23.90, NULL, 5.00),
(11, 5, '2025-05-07 14:47:53', 'Pending', 13.90, NULL, 5.00),
(12, 5, '2025-05-07 14:56:32', 'Pending', 23.90, NULL, 5.00);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `PaymentID` int(11) NOT NULL,
  `OrderID` int(11) DEFAULT NULL,
  `PaymentDate` timestamp NOT NULL DEFAULT current_timestamp(),
  `PaymentMethod` varchar(50) DEFAULT NULL,
  `AmountPaid` decimal(10,2) DEFAULT NULL,
  `PaymentStatus` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`PaymentID`, `OrderID`, `PaymentDate`, `PaymentMethod`, `AmountPaid`, `PaymentStatus`) VALUES
(1, 1, '2025-05-07 13:24:14', 'Cash on Delivery', 18.90, 'Paid'),
(2, 2, '2025-05-07 13:29:43', 'Cash on Delivery', 18.90, 'Paid'),
(3, 3, '2025-05-07 13:43:00', 'Cash on Delivery', 6.90, 'Paid'),
(4, 4, '2025-05-07 13:44:16', 'Cash on Delivery', 18.90, 'Paid'),
(5, 5, '2025-05-07 13:49:11', 'Cash on Delivery', 37.80, 'Paid'),
(6, 6, '2025-05-07 13:52:23', 'Cash on Delivery', 18.90, 'Paid'),
(7, 7, '2025-05-07 13:54:23', 'Cash on Delivery', 8.90, 'Paid'),
(8, 8, '2025-05-07 13:56:22', 'Cash on Delivery', 39.90, 'Paid'),
(9, 9, '2025-05-07 14:01:08', 'Cash on Delivery', 18.90, 'Paid'),
(10, 10, '2025-05-07 14:40:38', 'Cash on Delivery', 23.90, 'Paid'),
(11, 11, '2025-05-07 14:47:53', 'Cash on Delivery', 13.90, 'Paid'),
(12, 12, '2025-05-07 14:56:32', 'Cash on Delivery', 23.90, 'Paid');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `ProductID` int(11) NOT NULL,
  `CategoryID` int(11) DEFAULT NULL,
  `ProductName` varchar(255) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Price` decimal(10,2) DEFAULT NULL,
  `Quantity` int(11) DEFAULT NULL,
  `ProdIMG1` varchar(255) DEFAULT NULL,
  `ProdIMG2` varchar(255) DEFAULT NULL,
  `ProdIMG3` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`ProductID`, `CategoryID`, `ProductName`, `Description`, `Price`, `Quantity`, `ProdIMG1`, `ProdIMG2`, `ProdIMG3`) VALUES
(1, 1, 'The Ordinary Niacinamide 10% + Zinc 1% (30ml)', 'A high-strength vitamin and mineral formula that visibly targets the appearance of blemishes, large pores, and oily skin. Niacinamide (Vitamin B3) is scientifically proven to help reduce the look of skin blemishes and congestion. This formula is further supported with zinc salt of pyrrolidone carboxylic acid to balance visible aspects of sebum activity.', 42.00, 1000, '6811009113629_product1.webp', '6811009113a08_product1.jpg', '6811009113f01_product1.jpeg'),
(2, 2, 'Maybelline Fit Me Matte + Poreless Liquid Foundation – 120 Classic Ivory (30ml)', 'Maybelline Fit Me Matte + Poreless Foundation is a lightweight liquid foundation designed to provide a natural, seamless matte finish. Specially formulated for normal to oily skin, it refines pores and controls shine for a smooth, even complexion. With micro-powders that blur pores and absorb oil, your skin will look naturally flawless all day.', 45.90, 1000, '68124b580c669_images.jpeg', '68124b580cb71_download (2).jpeg', '68124b580ce15_download (1).jpeg'),
(3, 1, 'EUCERIN Spotless Brightening Spot Corrector 5ml', 'Moisturizer for chronic dry, sensitive skin. Non-irritating. Non-sensitizing. No fragrances, parabens or lanolin. Non-comedogenic.', 114.00, 999, 'WTCMY-87714-front-zoom.avif', 'WTCMY-87714-back-zoom.avif', 'WTCMY-87714-alt1-zoom.avif'),
(4, 1, 'CETAPHIL Moisturizing Cream For Face & Body 100g', 'Moisturizer for chronic dry, sensitive skin. Non-irritating. Non-sensitizing. No fragrances, parabens or lanolin. Non-comedogenic.', 72.00, 1000, 'WTCMY-59383-side-zoom.avif', 'WTCMY-59383-back-zoom.avif', NULL),
(5, 2, 'WET N WILD Megalast Eyeshadow Primer', 'Get the most out of your eyeshadow with this ultra creamy and lightweight eyeshadow primer. This primer dries clear and makes it easy for eyeshadow to cling to the lid even sparkle and glitter shadows. Enriched with antioxidant rich vitamin E this eyeshadow primer helps eyeshadow adhere to the lid with ease.', 36.90, 1000, 'WTCMY-1001261-back-zoom.avif', 'WTCMY-1001261-side-zoom.avif', NULL),
(6, 2, 'MAYBELLINE Super Stay Fixer Spray', 'Super Stay Double Fixer Spray can lock 24H full face makeup, which include eye shadow and eyebrow makeup! Contains vitamin E to help moisturize the skin and plant squalane, which moisturizes and locks in moisture! Refers to the milky white formula on the top phase of the product', 49.90, 1000, 'WTCMY-1014577-side-zoom.avif', 'WTCMY-1014577-back-zoom.avif', 'WTCMY-1014577-alt3-zoom.avif'),
(7, 5, 'GAVISCON Heartburn & Indigestion Relief Peppermint Bottle 200ml', 'For oral use. Shake well before use. Adults and children over 12 years: Take 10-20ml (two to four 5ml spoonfuls) after meals and at bedtime, up to four times a day. Children under 12 years: Not to be given unless recommended by your doctor.', 44.90, 1000, 'WTCMY-11855-front-zoom.avif', 'WTCMY-11855-side-zoom.avif', NULL),
(8, 5, 'COUNTERPAIN Analgesic Balm 60G', 'This product is used to treat minor aches and pains of the muscles/joints (e.g., arthritis, backache, sprains). Menthol and methyl salicylate are known as counterirritants. They work by causing the skin to feel cool and then warm.', 17.50, 1000, 'WTCMY-11520-front-zoom.avif', 'WTCMY-11520-back-zoom.avif', 'WTCMY-11520-side-zoom.avif'),
(9, 3, 'ANTABAX Anti Bacterial Shower Cool 960ml', 'Antabax cleans and removes 99.9% of germs to provide your family all-day protection. Dermatologically tested to be gentle on the skin, its formulation comes with additional essences and special ingredients, including anti-oxidant and anti-inflammatory properties of Vitamin C and E, as well as cell rejuvenating effects of essential Vitamin B Complex (B3, B5 & B6)', 18.90, 1000, 'WTCMY-58644-side-zoom.avif', 'WTCMY-58644-back-zoom.avif', 'WTCMY-58644-alt1-zoom.avif'),
(10, 7, 'DARLIE All Shiny White Charcoal Clean Toothpaste 2 x 140g', 'Darlie All Shiny White charcoal clean is powered by charcoal particles with natural absorption to thoroughly clean your mouth with Speedy whitening Agent (SWA) particles, the toothpaste delivers benefits of enhanced whitening and cleaning power.', 20.50, 1000, 'WTCMY-74574-side-zoom.avif', 'WTCMY-74574-back-zoom.avif', NULL),
(11, 4, 'DOVE Intense Repair Damaged Hair Shampoo 680 ml', 'Dove Intense Repair Shampoo, formulated with Keratin Repair Actives, helps your hair to recover from damage in two different ways. The formula repairs signs of surface damage, making your hair look and feel smoother and stronger against breakage. It also penetrates the strands to provide hair nourishment deep inside, making your hair look healthier, wash after wash', 30.50, 1000, 'WTCMY-55224-front-zoom.avif', 'WTCMY-55224-back-zoom.avif', NULL),
(12, 4, 'CLEAR MEN Deep Cleanse Anti-Dandruff Shampoo 315ml', 'New Clear Men with Taurine and Triple Anti-Dandruff Technology, consist of Guar BB18, Niacinamide (Vitamin B3) and Amino Acid, to energize scalp\'s self defence** to remove, resist and prevent dandruff*. End recurring dandruff concern* with CLEAR. Activated Charcoal and Citrus Peel are known to absorb and neutralise grease, and nourish scalp.', 20.50, 1000, 'WTCMY-11483-side-zoom.avif', 'WTCMY-11483-alt1-zoom.avif', NULL),
(13, 6, 'JOHNSON\'S Baby Milk + Rice Lotion 500ml', 'Johnson\'s ® Milk + Rice lotion with natural milk and rice to help complete skin nourishment, with up to 24 hours moisturization. Baby skin loses moisture more quickly than adult skin and still needs gentle care. We have specially designed these products with moisturizers to help keep babies\' skin nourished as they grow.', 29.30, 1000, 'WTCMY-40057-front-zoom.avif', 'WTCMY-40057-back-zoom.avif', NULL),
(14, 6, 'CARRIE JUNIOR Hair & Body Wash Groovy Grapeberry 280g', 'Specially formulated with safe & gentle with mild cleansing agent, cares for the delicate skin. Parents can be rest assured to use CARRIE JUNIOR on their kids (or even babies) as it is specially made to suit children\'s skin & scalp.', 10.90, 1000, 'WTCMY-21502-front-zoom.avif', 'WTCMY-21502-side-zoom.avif', NULL),
(15, 3, 'NIVEA Deo Female Extra Bright Velvet Romance Spray 150ML', 'NIVEA Extra Bright Velvet Romance Deodorant with exclusive world class perfumed Peony fragrance that exude sexy and irresistibly mysterious scent. Also contains with 10X Vitamin C for bright and silky smooth underarms and lasting premium perfumed fragrance for all day long that makes you will feel amazingly confident and beautiful all day long', 22.90, 1000, 'WTCMY-1000320-side-zoom.avif', 'WTCMY-1000320-back-zoom.avif', NULL),
(16, 7, 'Listerine Cool Mint 250ML', 'Listerine® Cool Mint kills 99.9% of germs that cause bad breath for fresher breath. Unique formula with 4 essential oils deeply penetrates to kill bacteria in the plaque biofilm.Recommended for daily use.', 12.90, 1000, 'WTCMY-24481-front-zoom.avif', 'WTCMY-24481-back-zoom.avif', NULL),
(17, 8, 'Libresse Maxi Night Wings 32cm 2x12s', 'Protection Starts With The Right Fit Isn\'T It Great When You Find The Right Fit. Our Exclusive Secure Fit Pad With Deep Flow Channels (Dfc) Is Uniquely Shaped To Securely Stay In Place And Hug Your Curves Comfortably. The Fluid Is Directed Quickly Into The Pad To Help Prevent Leakage. With Such Protection, You\'Ll Get A Good Night\'S Sleep.', 13.50, 1000, 'WTCMY-73311-front-zoom.avif', 'WTCMY-73311-back-zoom.avif', NULL),
(18, 8, 'SOFY Cooling Fresh Night Slim Wing 35cm 9\'s', 'From the No.1 Brand in Japan, SOFY Cooling Fresh relieves stuffy discomfort by giving you 5 hours^ long cool sensation. With improved Japan technology cool mint sheet and Japanese technology instant absorption, you will feel fresh during period even out of home for long hours! Cooling Fresh has natural plant essential oil for refreshing scent.', 9.90, 1000, 'WTCMY-61454-side-zoom.avif', 'WTCMY-61454-back-zoom.avif', NULL),
(19, 1, 'BIO-ESSENCE 24K BG Gold Rose Water 30ml', 'Enriched with 24K Bio-Gold Flakes, the amazing anti-oxidant to fight 1st sign of wrinkles and infused with Japanese Rose Extract, Eijitsu offers ultra hydration and help to refine pores so that skin feels petal-soft & moistful.', 25.90, 1000, 'WTCMY-98000-back-zoom.avif', 'WTCMY-98000-side-zoom.avif', 'WTCMY-98000-front-zoom.avif'),
(20, 1, 'OLAY Total Effects Night Facial Cream', 'Olay Total Effects 7 In One Night Cream 50g Nourishing night cream for 7 signs of youthful-looking skin. Nourishes for soft & smooth skin. Helps revive tired-looking skin to give healthy fresh radiance. Reduces look of fine lines & spots', 44.90, 1000, 'WTCMY-56247-side-zoom.avif', 'WTCMY-56247-front-zoom.avif', 'WTCMY-56247-back-zoom.avif'),
(21, 2, 'PERIPERA Speedy Skinny Brow #1 Gray Brown 1\'s', 'An ultra-skinny 1.5mm eyebrow pencil that gives you a precise application and natural-looking eyebrows.', 29.00, 1000, 'WTCMY-78109-back-zoom.avif', 'WTCMY-78109-front-axY6GElR-zoom.avif', 'WTCMY-78109-side-zoom.avif'),
(22, 3, 'SKINTIFIC Perfect Stay Velvet Matte Cushion 00 Porcelain 11g', 'Cushion with a velvet matte finish, smooth matte and makes the final appearance flawless and looks like a healthy skin. High coverage that can cover dark spots, panda eyes and skin imperfections in 1 tap. 12 hours of oil control and long lasting with Smart Oil Control technology, absorbs oil so skin is shine-free but remains hydrated', 79.00, 1000, 'WTCMY-1011966-back-zoom.avif', 'WTCMY-1011966-front-LowQKX2f-zoom.avif', 'WTCMY-1011966-side-zoom.avif'),
(23, 3, 'DOVE Deodorant Roll On Peach & Lemon Verbena 50ml', 'Stay naturally fresh all day with this deo made with NO aluminum, NO alcohol, and 1/4 moisturizing cream. Just the essentials you need for 24HR odor protection and smooth underarms nothing more, nothing less. Plus, this gentle formula is non-darkening too!', 18.50, 1000, '1014030_front-n4DfBa1M-zoom.avif', '1014030_side-n4DfBa1M-zoom.avif.avif', '1014030_supporting-n4DfBa1M-zoom.avif'),
(24, 3, 'NIVEA Deo Female Pearl & Beauty Shaveless Roll On 2 x 50ml', 'NIVEA Pearl & Beauty Shave-less with power of 5X Radiance with precious white pearl extract for pearly radiant skin with lesser shavings. Innovative formula with Pilisoft, giving reduced hair growth feeling, thus helps to lessen frequency of hair removal. Lasting fragrance of fresh florals.', 26.90, 1000, 'WTCMY-1013534-back-zoom.avif', 'WTCMY-1013534-alt1-zoom.avif', 'WTCMY-1013534-alt2-zoom.avif'),
(25, 3, 'REXONA Vitamin Bright Peach Spray Deo 135ml', 'Rexona Women Spray Vitamin Bright Peach helps penetrate through skin layer^ for bright and glowing underarm skin^^. With 70x Vitamin C\' and delicately crafted peach fragrance inspired by world-class perfume. ^within epidermis. based on clinical test, results may vary. vs. another Unilever brightening deo.', 18.90, 1000, 'WTCMY-1010718-side-zoom.avif', 'WTCMY-1010718-front-zoom.avif', NULL),
(26, 4, 'SUNSILK Anti Dandruff Shampoo 300ml', 'Promote a reduced usage of virgin materials by using recycled content instead. Bottle is made with 25% post recycled content - Anti-Dandruff Solution - Dr. Francesca Fusco Scalp Care Expert - With Zpt Citrus Complex - For A Restored Dandruff-Free* Scalp', 13.50, 1000, 'WTCMY-68746-side-zoom.avif', 'WTCMY-68746-front-zoom.avif', 'WTCMY-68746-swatch-zoom.avif'),
(27, 5, 'VICKS Baby Balsam Moisturising & Soothing Baby Care Rub 50g', 'Use only as intended. Gently massage on chest, neck, back and soles of feet to help soothe and comfort.', 19.30, 1000, 'WTCMY-25918-front-zoom.avif', 'WTCMY-25918-back-zoom.avif', 'WTCMY-25918-alt1-zoom.avif'),
(28, 5, 'BYE BYE FEVER Babies 4\'s - Cooling Gel Sheet', NULL, 11.80, 1000, 'WTCMY-42142-front-zoom.avif', 'WTCMY-42142-side-zoom.avif', NULL),
(29, 6, 'MAMYPOKO Air Fit Baby Girl Disposable Diapers XL 38\'s', 'MamyPoko Pants Air Fit with its Air Fit Gathers around the thighs fit closely around the leg cuffs leaving no gap for leakages. The cloth like Soft Stretchy material fits gently around the waist to prevent leakages even when baby moves. MamyPoko Air Fit is the No. 1 Mother\'s choice of diaper in Japan.', 60.90, 998, 'WTCMY-84416-front-zoom.avif', NULL, NULL),
(30, 6, 'VASELINE Baby Protecting Jelly 50ml', 'Vaseline® Baby Protecting Jelly locks moisture to help protect baby\'s skin from discomfort with a light baby powder fragrance. Made from triple-purified petrolatum. Purity guaranteed. Forms a protective barrier to keep out wetness and protect your baby\'s skin', 6.50, 1000, 'WTCMY-34640-front-zoom.avif', 'WTCMY-34640-back-zoom.avif', NULL),
(31, 7, 'DARLIE Toothpaste Double Action Jumbo 250g', 'The perfect combination of spearmint and peppermint refreshes your morning, giving you a cooling sensation and freshened breath. With this confidence-boosting freshness, your smile will draw people closer - Enriched with natural spearmint & peppermint essence', 15.50, 1000, 'WTCMY-20612-back-zoom.avif', 'WTCMY-20612-front-zoom.avif', 'WTCMY-20612-side-zoom.avif'),
(32, 7, 'SENSODYNE Sensitivity & Gum Toothpaste 100g', 'Sensodyne Sensitivity & Gum toothpaste is a clinically proven daily dual action toothpaste for people with sensitive teeth and gum problems. Its dual action formula works in two ways. It builds a protective layer over sensitive areas and it targets and removes plaque bacteria to help support good gum health.', 19.90, 1000, 'WTCMY-21950-front-zoom.avif', 'WTCMY-21950-side-zoom.avif', NULL),
(33, 8, 'KOTEX Longer & Wider Scented Pantyliner 17.5cm (30s) - Odor Care with Daun Sirih Extract Liners', 'Enjoy extra comfort and confidence with Kotex Longer & Wider Scented Panty Liners 17.5cm. These liners come with Daun Sirih Extract, offer light and breathable protection, so you stay fresh and dry every day!', 6.90, 999, 'WTCMY-1000821-side-zoom.avif', 'WTCMY-1000821-front-zoom.avif', 'WTCMY-1000821-back-zoom.avif'),
(34, 8, 'CAREFREE Super Dry Shower Fresh Scent Liners 2x50s', 'Shaped for a natural and comfortable fit. Moisture proof backing for protection against leakage. Soft cover designed for dryness and comfort. Dermatologically tested against allergy and irritation', 18.90, 998, 'WTCMY-41563-back-zoom.avif', 'WTCMY-41563-front-zoom.avif', 'WTCMY-41563-side-zoom.avif'),
(35, 9, 'DUREX Condom Close Fit 12s', 'Why You\'ll Love Durex Close Fit: Durability, Reliability, Excellence Since 1929. Size, Comfortable Fit, Small Size (Nominal Width: 49Mm). Latex Condoms With Regular Silicone Lube, Easy-On, Teat Ended Smooth Shape To Be Easier To Put On And Provide A Better Fit During Sex.', 39.90, 999, 'WTCMY-11968-side-zoom.avif', 'WTCMY-11968-front-zoom.avif', 'WTCMY-11968-back-zoom.avif'),
(36, 9, 'ONE Extended Pleasures Condom 3\'s', 'ONE Extended Pleasures is the way to go for a good and long time. Softer latex condoms that features a lubricant with benzocaine, a mild male genital desensitizer, to help prevent premature ejaculation, extends performance time, and boosts self esteem in the bedroom. Safe, non prescriptive and improves premature ejaculation P.E. with repeated use.', 8.90, 998, 'WTCMY-51194-front-zoom.avif', 'WTCMY-51194-back-zoom.avif', 'WTCMY-51194-alt1-zoom.avif'),
(37, 9, 'DUREX Condom Fetherlite Ultima Extra Thin 3s', 'Why You\'ll Love Durex Fetherlite Ultima, extra Thin For Enhanced Sensitivity, ofers Increased Sensitivity. Increasing The Feeling Of Closeness To Your Partner Without Sacrificing Safety. Sleek Fit And Feel.', 18.90, 993, 'WTCMY-15483-side-zoom.avif', 'WTCMY-15483-front-zoom.avif', 'WTCMY-11968-back-zoom.avif'),
(38, 9, 'DUREX Vibrating Ring Intense 1s', 'Why You\'ll Love Durex Play Vibrations Ring- Provides Up To 20 Minutes Pulsating Sensations For Her And For Him.- Can Help Him Stay Harder For Longer.- Super-Stretchy And Soft For Comfort.- Can Increase Pleasure For Both Partners.- Reusable Up To 6 Times.- It Is Battery Operated And Easy To Switch On/Off.- Can Be Used With Condoms, Lube Or Pleasure Gels.- Easy To Wear, Replaceable Battery.- Waterproof.', 40.00, 1000, 'WTCMY-14964-front-zoom.avif', 'WTCMY-14964-back-zoom.avif', 'WTCMY-14964-alt2-zoom.avif');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `StaffID` int(11) NOT NULL,
  `StaffUsername` varchar(100) DEFAULT NULL,
  `StaffName` varchar(100) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Contact` varchar(20) DEFAULT NULL,
  `StaffProfilePhoto` varchar(255) DEFAULT NULL,
  `StaffStatus` enum('Active','Inactive') DEFAULT 'Active',
  `FirstTimeLogin` tinyint(1) DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`StaffID`, `StaffUsername`, `StaffName`, `Password`, `Email`, `Contact`, `StaffProfilePhoto`, `StaffStatus`, `FirstTimeLogin`, `CreatedAt`) VALUES
(1, 'hi', 'hi', '$2y$10$FoxITdw.Bc0MPL4bXYyJL.HyGVkrKTs0TBUQhxZePoKJdRckX7W12', 'hi123@gmail.com', '011-34124521', '681b5b29ae4e6_1455aeb98a83333ac25f492047ce0547.jpg', 'Active', 0, '2025-05-07 13:07:06');

-- --------------------------------------------------------

--
-- Table structure for table `token`
--

CREATE TABLE `token` (
  `id` varchar(255) NOT NULL,
  `expire` datetime NOT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voucher`
--

CREATE TABLE `voucher` (
  `VoucherID` int(11) NOT NULL,
  `Code` varchar(50) NOT NULL,
  `Discount` decimal(5,2) NOT NULL,
  `ExpiryDate` date NOT NULL,
  `Description` text DEFAULT NULL,
  `Status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `UpdatedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `voucher`
--

INSERT INTO `voucher` (`VoucherID`, `Code`, `Discount`, `ExpiryDate`, `Description`, `Status`, `CreatedAt`, `UpdatedAt`) VALUES
(2, 'HI123', 100.00, '2025-05-09', 'Bye', 'Active', '2025-05-06 19:02:07', '2025-05-06 19:02:07'),
(3, 'gg123', 12.00, '2025-05-08', 'Bye', 'Active', '2025-05-06 19:05:25', '2025-05-06 20:15:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`CartID`),
  ADD KEY `MemberID` (`MemberID`);

--
-- Indexes for table `cartitem`
--
ALTER TABLE `cartitem`
  ADD PRIMARY KEY (`CartItemID`),
  ADD KEY `CartID` (`CartID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`CategoryID`);

--
-- Indexes for table `invoice`
--
ALTER TABLE `invoice`
  ADD PRIMARY KEY (`InvoiceID`),
  ADD KEY `OrderID` (`OrderID`);

--
-- Indexes for table `manager`
--
ALTER TABLE `manager`
  ADD PRIMARY KEY (`ManagerID`),
  ADD UNIQUE KEY `ManagerUsername` (`ManagerUsername`);

--
-- Indexes for table `member`
--
ALTER TABLE `member`
  ADD PRIMARY KEY (`MemberID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `orderitem`
--
ALTER TABLE `orderitem`
  ADD PRIMARY KEY (`OrderItemID`),
  ADD KEY `OrderID` (`OrderID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `MemberID` (`MemberID`),
  ADD KEY `VoucherID` (`VoucherID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`PaymentID`),
  ADD KEY `OrderID` (`OrderID`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`ProductID`),
  ADD KEY `CategoryID` (`CategoryID`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`StaffID`),
  ADD UNIQUE KEY `StaffUsername` (`StaffUsername`);

--
-- Indexes for table `token`
--
ALTER TABLE `token`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `voucher`
--
ALTER TABLE `voucher`
  ADD PRIMARY KEY (`VoucherID`),
  ADD UNIQUE KEY `Code` (`Code`),
  ADD UNIQUE KEY `Code_2` (`Code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `CartID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cartitem`
--
ALTER TABLE `cartitem`
  MODIFY `CartItemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `CategoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `invoice`
--
ALTER TABLE `invoice`
  MODIFY `InvoiceID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `manager`
--
ALTER TABLE `manager`
  MODIFY `ManagerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `member`
--
ALTER TABLE `member`
  MODIFY `MemberID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orderitem`
--
ALTER TABLE `orderitem`
  MODIFY `OrderItemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `PaymentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `ProductID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `StaffID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `voucher`
--
ALTER TABLE `voucher`
  MODIFY `VoucherID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`MemberID`) REFERENCES `member` (`MemberID`);

--
-- Constraints for table `cartitem`
--
ALTER TABLE `cartitem`
  ADD CONSTRAINT `cartitem_ibfk_1` FOREIGN KEY (`CartID`) REFERENCES `cart` (`CartID`),
  ADD CONSTRAINT `cartitem_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`);

--
-- Constraints for table `invoice`
--
ALTER TABLE `invoice`
  ADD CONSTRAINT `invoice_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `orders` (`OrderID`);

--
-- Constraints for table `orderitem`
--
ALTER TABLE `orderitem`
  ADD CONSTRAINT `orderitem_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `orders` (`OrderID`),
  ADD CONSTRAINT `orderitem_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`MemberID`) REFERENCES `member` (`MemberID`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`VoucherID`) REFERENCES `voucher` (`VoucherID`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `orders` (`OrderID`);

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`CategoryID`) REFERENCES `category` (`CategoryID`);

--
-- Constraints for table `token`
--
ALTER TABLE `token`
  ADD CONSTRAINT `token_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `member` (`MemberID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
