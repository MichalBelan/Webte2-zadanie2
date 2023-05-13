<?php 
// class Day {
//     const MONDAY = "monday";
//     const TUESDAY = "tuesday";
//     const WEDNESDAY = "wednesday";
//     const THURSDAY = "thursday";
//     const FRIDAY = "friday";
//     const SATURDAY = "saturday";
//     const SUNDAY = "sunday";

//     public static function from($number) {
//         switch ($number) {
//             case 1:
//                 return self::MONDAY;
//             case 2:
//                 return self::TUESDAY;
//             case 3:
//                 return self::WEDNESDAY;
//             case 4:
//                 return self::THURSDAY;
//             case 5:
//                 return self::FRIDAY;
//             case 6:
//                 return self::SATURDAY;
//             case 7:
//                 return self::SUNDAY;
//             default:
//                 throw new Exception("Invalid day number");
//         }
//     }

//     public static function isValid($day) {
//         return in_array($day, [
//             self::MONDAY,
//             self::TUESDAY,
//             self::WEDNESDAY,
//             self::THURSDAY,
//             self::FRIDAY,
//             self::SATURDAY,
//             self::SUNDAY
//         ]);
//     }
// }

enum Day : int
{
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;
    case Sunday = 7;

}


?>
